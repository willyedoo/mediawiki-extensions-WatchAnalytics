<?php
/**
 * MediaWiki Extension: WatchAnalytics
 * http://www.mediawiki.org/wiki/Extension:WatchAnalytics
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 */

/**
 *
 * @file
 * @ingroup Extensions
 * @author James Montalvo
 * @licence MIT License
 */

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if ( !defined( 'MEDIAWIKI' ) ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/WatchAnalytics/WatchAnalytics.php" );
EOT;
	exit( 1 );
}

class UserWatchesQuery extends WatchesQuery {

	public $sqlUserName = 'u.user_name AS user_name';
	public $sqlNumWatches = 'COUNT(*) AS num_watches';
	public $sqlNumPending = 'SUM( IF(w.wl_notificationtimestamp IS NULL, 0, 1) ) AS num_pending';
	public $sqlPercentPending = 'SUM( IF(w.wl_notificationtimestamp IS NULL, 0, 1) ) * 100 / COUNT(*) AS percent_pending';
	public $sqlRevisionFrequencyScore = 'IFNULL( rev.rev_frequency_score, 0 ) AS rev_frequency_score';

	protected $fieldNames = array(
		'user_name'               => 'watchanalytics-special-header-user',
		'num_watches'             => 'watchanalytics-special-header-watches',
		'num_pending'             => 'watchanalytics-special-header-pending-watches',
		'percent_pending'         => 'watchanalytics-special-header-pending-percent',
		'max_pending_minutes'     => 'watchanalytics-special-header-pending-maxtime',
		'avg_pending_minutes'     => 'watchanalytics-special-header-pending-averagetime',
		'rev_frequency_score'     => 'watchanalytics-special-header-rev-frequency',
	);

	public function getMaxRevisions () {

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			'revision',
			array( 'rev_user', 'COUNT(*) AS num_revisions' ),
			null,
			__METHOD__,
			array(
				'GROUP BY' => 'rev_user',
				'ORDER BY' => 'num_revisions DESC',
			)
			// array() // need to join user table to make sure user exists?
		);
		
		$row = $dbr->fetchRow( $res );

		if ( $row === false ) {
			// there are literally no revisions by any users....I don't think
			// this is possible, but return 1 for good measure (so as not to
			// divide by zero)
			return 1;
		}

		return $row[ 'num_revisions' ];

	}

	public function getQueryInfo( $conds = null ) {

		$maxRevisions = $this->getMaxRevisions();

		$this->tables = array(
			'w' => 'watchlist',
			'u' => 'user',
			'p' => 'page',
			'log' => 'logging',
			'rev' => 
				"(SELECT rev_user, COUNT( * ) / $maxRevisions AS rev_frequency_score"
				. ' FROM revision'
				. ' WHERE rev_timestamp >= 19000000000000'
				. ' GROUP BY rev_user)',
		);

		$this->fields = array(
			$this->sqlUserName,
			$this->sqlNumWatches,
			$this->sqlNumPending,
			$this->sqlPercentPending,
			$this->sqlMaxPendingMins,
			$this->sqlAvgPendingMins,
			$this->sqlRevisionFrequencyScore,
		);
		
		$this->conds = $conds ? $conds : array();

		$this->join_conds = array(
			'u' => array(
				'LEFT JOIN', 'u.user_id=w.wl_user'
			),
			'p' => array(
				'LEFT JOIN', 'p.page_namespace=w.wl_namespace AND p.page_title=w.wl_title'
			),
			'log' => array(
				'LEFT JOIN', 
				'log.log_namespace = w.wl_namespace '
				. ' AND log.log_title = w.wl_title'
				. ' AND p.page_namespace IS NULL'
				. ' AND p.page_title IS NULL'
				. ' AND log.log_action = "delete"'
			),
			'rev' => array(
				'LEFT JOIN', 'rev.rev_user = u.user_id'
			),
		);

		// optionally join the 'user_groups' table to filter by user group
		if ( $this->userGroupFilter ) {
			$this->tables['ug'] = 'user_groups';
			$this->join_conds['ug'] = array(
				'RIGHT JOIN', "w.wl_user = ug.ug_user AND ug.ug_group = \"{$this->userGroupFilter}\""
			);

			$noNullUsers = 'w.wl_user IS NOT NULL';
			if ( is_array( $this->conds ) ) {
				$this->conds[] = $noNullUsers;
			}
			else if ( is_string( $this->conds ) ) {
				$this->conds .= ' AND ' . $noNullUsers;
			}
		}

		// optionally join the 'categorylinks' table to filter by page category
		if ( $this->categoryFilter ) {
			$this->tables['cat'] = 'categorylinks';
			$this->join_conds['cat'] = array(
				'RIGHT JOIN', "cat.cl_from = p.page_id AND cat.cl_to = \"{$this->categoryFilter}\""
			);
		}


		$this->options = array(
			'GROUP BY' => 'w.wl_user'
		);
		
		return parent::getQueryInfo();

	}

	/**
	 * Gets watch statistics for a particular user.
	 * 
	 * @param User $user: the user to get watch-info on.
	 * @return array returns user watch info in an array with keys the same as
	 * $this->fieldNames.
	 */
	public function getUserWatchStats ( User $user ) {
	
		$qInfo = $this->getQueryInfo();

		$dbr = wfGetDB( DB_SLAVE );

		$res = $dbr->select(
			$qInfo['tables'],
			$qInfo['fields'],
			'w.wl_user=' . $user->getId(),
			__METHOD__,
			$qInfo['options'],
			$qInfo['join_conds']
		);
		
		$row = $dbr->fetchRow( $res );

		// if user doesn't have any pages in watchlist, then no data will be
		// returned by this query. Create a "blank" row instead.
		if ( $row === false ) {
			$row = array();
			foreach( $this->fieldNames as $name => $msg ) {
				$row[ $name ] = 0;
			}
			$row[ 'user_name' ] = $user->getName();
		}

		return $row;
	}

}
