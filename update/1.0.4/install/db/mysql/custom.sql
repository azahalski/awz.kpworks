CREATE TABLE IF NOT EXISTS `b_awz_kpworks_custom_appparams` (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `ACTIVE` varchar(1) NOT NULL,
    `NAME` varchar(64) NOT NULL,
    `SORT` int(4) NOT NULL,
    `PARAMS` longtext NOT NULL,
    `PORTAL` varchar(65) NOT NULL,
    `APP` varchar(65) NOT NULL,
    `DATE_ADD` datetime NOT NULL,
    PRIMARY KEY (`ID`),
    INDEX IX_PORTAL_APP (PORTAL,APP)
    );
CREATE TABLE IF NOT EXISTS `b_awz_kpworks_applog` (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `ENTITY_ID` varchar(64) NOT NULL,
    `PARAMS` longtext NOT NULL,
    `PORTAL` varchar(64) NOT NULL,
    `APP` varchar(64) NOT NULL,
    `DATE_ADD` datetime NOT NULL,
    PRIMARY KEY (`ID`),
    INDEX IX_PORTAL_APP (PORTAL,APP),
    INDEX IX_PORTAL_APP_ENT (PORTAL,APP,ENTITY_ID)
    );