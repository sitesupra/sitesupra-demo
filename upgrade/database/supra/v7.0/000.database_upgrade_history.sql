-- @supra:upgrade
-- @supra:createsTable database_upgrade_history

-- Database upgrade history table
CREATE TABLE database_upgrade_history (
	id INTEGER AUTO_INCREMENT PRIMARY KEY,
	filename VARCHAR(255) NOT NULL UNIQUE,
	md5sum VARCHAR(32) NOT NULL,
	upgrade_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
	output TEXT
);
