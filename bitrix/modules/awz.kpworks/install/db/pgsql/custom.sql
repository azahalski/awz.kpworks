CREATE TABLE IF NOT EXISTS b_awz_kpworks_custom_appparams (
    ID SERIAL NOT NULL,
    ACTIVE varchar(1) NOT NULL,
    NAME varchar(64) NOT NULL,
    SORT integer NOT NULL,
    PARAMS text NOT NULL,
    PORTAL varchar(65) NOT NULL,
    APP varchar(65) NOT NULL,
    DATE_ADD timestamp NOT NULL,
    PRIMARY KEY (ID)
);
CREATE INDEX IF NOT EXISTS IX_PORTAL_APP ON b_awz_kpworks_custom_appparams (PORTAL, APP);

CREATE TABLE IF NOT EXISTS b_awz_kpworks_applog (
    ID SERIAL NOT NULL,
    ENTITY_ID varchar(64) NOT NULL,
    PARAMS text NOT NULL,
    PORTAL varchar(64) NOT NULL,
    APP varchar(64) NOT NULL,
    DATE_ADD timestamp NOT NULL,
    PRIMARY KEY (ID)
);
CREATE INDEX IF NOT EXISTS IX_PORTAL_APP ON b_awz_kpworks_applog (PORTAL, APP);
CREATE INDEX IF NOT EXISTS IX_PORTAL_APP_ENT ON b_awz_kpworks_applog (PORTAL, APP, ENTITY_ID);
