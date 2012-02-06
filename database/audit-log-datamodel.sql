CREATE TABLE `su_AuditLog` (
  `id` BIGINT  NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(255)  NOT NULL,
  `component` VARCHAR(255)  NOT NULL,
  `action` VARCHAR(255),
  `message` VARCHAR(255) ,
  `user` VARCHAR(255) ,
  `data` MEDIUMBLOB ,
  `datetime` TIMESTAMP  NOT NULL,
  PRIMARY KEY (`id`)
)
ENGINE = MyISAM
COMMENT = 'Audit log table';	
	