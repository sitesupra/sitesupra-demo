-- @supra:upgrade
-- @supra:createsTable su_SessionRecord

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `su_SessionRecord`;

CREATE TABLE `su_SessionRecord` (
  `id` CHAR(40) NOT NULL,
  `name` VARCHAR(255)  NOT NULL,
  `dateCreated` TIMESTAMP NOT NULL,
  `data` BLOB,
  PRIMARY KEY (`id`)
)
ENGINE = MyISAM
COMMENT = 'Session records table';	
