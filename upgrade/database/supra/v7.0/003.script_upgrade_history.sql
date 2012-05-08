-- @supra:upgrade
-- @supra:createsTable script_upgrade_history

-- Upgrade script history table
CREATE TABLE script_upgrade_history (
	id INTEGER AUTO_INCREMENT PRIMARY KEY,
	filename VARCHAR(255) NOT NULL UNIQUE,
	md5sum VARCHAR(32) NOT NULL,
	upgrade_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
	output TEXT
);
