-- @supra:upgrade
-- @supra:createsTable su_AuditLog

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `su_AuditLog`;

CREATE TABLE `su_AuditLog` (
  `id` BIGINT  NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(255)  NOT NULL,
  `component` VARCHAR(255)  NOT NULL,
  `message` VARCHAR(255) ,
  `user` VARCHAR(255) ,
  `data` MEDIUMBLOB ,
  `datetime` TIMESTAMP  NOT NULL,
  PRIMARY KEY (`id`)
)
ENGINE = MyISAM
COMMENT = 'Audit log table';	
