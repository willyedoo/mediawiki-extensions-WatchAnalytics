CREATE TABLE watch_tracking_user (
    tracking_timestamp TIMESTAMP NOT NULL,
    user_id            INTEGER NOT NULL,
    num_watches        INTEGER NOT NULL,
    num_pending        INTEGER NOT NULL,
    event_notes        TEXT
);

CREATE INDEX watch_tracking_user_datapoint ON watch_tracking_user (tracking_timestamp, user_id);

CREATE TABLE watch_tracking_page (
    tracking_timestamp TIMESTAMP NOT NULL,
    page_id            INTEGER NOT NULL,
    num_watches        INTEGER NOT NULL,
    num_reviewed       INTEGER NOT NULL,
    event_notes        TEXT
);

CREATE UNIQUE INDEX watch_tracking_page_datapoint ON watch_tracking_page (tracking_timestamp, page_id);

CREATE TABLE watch_tracking_wiki (
    tracking_timestamp          TIMESTAMP NOT NULL,
    num_pages                   INTEGER NOT NULL,
    num_watches                 INTEGER NOT NULL,
    num_pending                 INTEGER NOT NULL,
    max_pending_minutes         INTEGER NOT NULL,
    avg_pending_minutes         INTEGER NOT NULL,
    num_unwatched               INTEGER NOT NULL,
    num_one_watched             INTEGER NOT NULL,
    num_unreviewed              INTEGER NOT NULL,
    num_one_reviewed            INTEGER NOT NULL,
    content_num_pages           INTEGER NOT NULL,
    content_num_watches         INTEGER NOT NULL,
    content_num_pending         INTEGER NOT NULL,
    content_max_pending_minutes INTEGER NOT NULL,
    content_avg_pending_minutes INTEGER NOT NULL,
    content_num_unwatched       INTEGER NOT NULL,
    content_num_one_watched     INTEGER NOT NULL,
    content_num_unreviewed      INTEGER NOT NULL,
    content_num_one_reviewed    INTEGER NOT NULL,
    event_notes                 TEXT
);

CREATE UNIQUE INDEX watch_tracking_wiki_datapoint ON watch_tracking_wiki (tracking_timestamp);
