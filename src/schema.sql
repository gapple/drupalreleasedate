
CREATE TABLE IF NOT EXISTS `estimates` (
  `version` tinyint(4) NOT NULL,
  `when` datetime NOT NULL,
  `estimate` datetime DEFAULT NULL,
  `note` varchar(255) NOT NULL DEFAULT '',
  `data` mediumblob NOT NULL,
  PRIMARY KEY (`version`,`when`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `samples` (
  `version` tinyint(4) NOT NULL,
  `when` datetime NOT NULL,
  `critical_bugs` smallint(6) NOT NULL,
  `critical_tasks` smallint(6) NOT NULL,
  `major_bugs` smallint(6) NOT NULL,
  `major_tasks` smallint(6) NOT NULL,
  `normal_bugs` smallint(6) DEFAULT NULL,
  `normal_tasks` smallint(6) DEFAULT NULL,
  `notes` varchar(127) NOT NULL DEFAULT '',
  PRIMARY KEY (`version`,`when`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
