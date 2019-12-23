CREATE TABLE `db` (
  `schema_version` MEDIUMINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `hosts` (
  `id` MEDIUMINT NOT NULL AUTO_INCREMENT,
  `mac_address` char(12) NOT NULL,
  `name` char(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mac` (`mac_address`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `host_details` (
  `id` MEDIUMINT NOT NULL AUTO_INCREMENT,
  `host_id` MEDIUMINT NOT NULL,
  `comment` char (128),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`host_id`) references hosts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `groups` (
  `id` MEDIUMINT NOT NULL AUTO_INCREMENT,
  `name` char(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `group_membership` (
  `id` MEDIUMINT NOT NULL AUTO_INCREMENT,
  `host_id` MEDIUMINT NOT NULL,
  `group_id` MEDIUMINT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (host_id) references hosts(id) ON DELETE CASCADE,
  FOREIGN KEY (group_id) references groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE UNIQUE INDEX idx_group_membership ON group_membership (host_id, group_id);

CREATE TABLE `host_configuration` (
  `host_id` MEDIUMINT NOT NULL,
  `configuration` LONGTEXT,
  PRIMARY KEY (`host_id`),
  FOREIGN KEY (`host_id`) references hosts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `global_configuration` (
  `id` enum('1') NOT NULL,
  `configuration` LONGTEXT DEFAULT "",
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

