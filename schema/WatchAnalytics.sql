CREATE TABLE watch_tracking_user (
    tracking_timestamp BYTEA NOT NULL,
    user_id            INTEGER NOT NULL,
    num_watches        INTEGER NOT NULL,
    num_pending        INTEGER NOT NULL,
    event_notes        VARCHAR(63)
);

CREATE INDEX watch_tracking_user_datapoint ON watch_tracking_user (tracking_timestamp, user_id);
CREATE INDEX watch_tracking_user_user ON watch_tracking_user (user_id);
CREATE INDEX watch_tracking_user_timestamp ON watch_tracking_user (tracking_timestamp);

CREATE TABLE watch_tracking_page (
    tracking_timestamp BYTEA NOT NULL,
    page_id            INTEGER NOT NULL,
    num_watches        INTEGER NOT NULL,
    num_reviewed       INTEGER NOT NULL,
    event_notes        VARCHAR(63)
);

CREATE UNIQUE INDEX watch_tracking_page_datapoint ON watch_tracking_page (tracking_timestamp, page_id);
CREATE INDEX watch_tracking_page_page ON watch_tracking_page (page
