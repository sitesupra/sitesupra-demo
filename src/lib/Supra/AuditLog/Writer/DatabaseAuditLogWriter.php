<?php

namespace Supra\AuditLog\Writer;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\ComponentConfiguration;
use Supra\User\Entity\User as UserEntity;

/**
 * Database audit log writer
 * 
 */
class DatabaseAuditLogWriter extends AuditLogWriterAbstraction
{

	const AUDIT_TABLE = 'su_AuditLog';
	
	private $connectionOptions;
	private $dbConnection;

	public function __construct($connectionOptions)
	{
		$this->connectionOptions = $connectionOptions;
	}

	public function write($level, $component, $message = '', $user = null, $data = array())
	{
		$tableName = self::AUDIT_TABLE;
		
		$message = (string) $message;

		if (empty($this->dbConnection)) {
			$this->dbConnection =
					\Doctrine\DBAL\DriverManager::getConnection($this->connectionOptions);
		}

		if (is_object($component)) {
			$componentConfig = ObjectRepository::getComponentConfiguration($component);

			if ($componentConfig instanceof ComponentConfiguration
					&& ! empty($componentConfig->title)
			) {
				$component = $componentConfig->class;
			} else {
				
				// workaround for user logout/login
				if (($component instanceof \Supra\Cms\AuthenticationPreFilterController)
						|| ($component instanceof \Supra\Cms\Logout\LogoutController)) {
					
					$component = 'Supra\Cms\InternalUserManager\InternalUserManagerController';
					
				} else {
					$component = get_class($component);
				}
			}
		} else {
			$component = (string) $component;
		}


		if ($user instanceof UserEntity) {
			$user = $user->getLogin();
		} else if ( ! empty($user)) {
			$user = (string) $user;
		} else {
			$user = '';
		}

		if ( ! empty($data)) {
			$data = serialize($data);
		} else {
			$data = null;
		}

		$query = "INSERT INTO {$tableName} (level, component, message, user, data) 
			VALUES (:level, :component, :message, :user, :data)";

		$params = array('level' => $level,
			'component' => $component,
			'message' => $message,
			'user' => $user,
			'data' => $data);

		try {
			$this->dbConnection->executeQuery($query, $params);
		} catch (\PDOException $e) {

			ObjectRepository::getLogger($this)->error("Can't write audit log record in to DB; {$e->getMessage()}");
			$auditWriter = new FileAuditLogWriter();
			$auditWriter->write($level, $component, $message, $user, $data);
		}
	}

}