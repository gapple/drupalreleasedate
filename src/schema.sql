
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
  `notes` varchar(127) NOT NULL DEFAULT '',
  PRIMARY KEY (`version`,`when`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sample_values` (
  `version` tinyint(4) NOT NULL,
  `when` datetime NOT NULL,
  `key` varchar(32) NOT NULL,
  `value` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`version`,`when`, `key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
