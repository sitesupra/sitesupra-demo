<?php

namespace Supra\Session\Handler;

use Supra\Session\Exception;
use Supra\ObjectRepository\ObjectRepository;

class DatabaseSessionHandler extends HandlerAbstraction
{
	
	/**
	 * Session records table name
	 */
	const TABLE_NAME = 'su_SessionRecord';
	
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	private $connection;
		
	/**
	 * @var boolean
	 */
	private $newRecord = false;
	
	/**
	 * @var string
	 */
	private $originalDataHash;

	/**
	 * Session id for the request must be detected manually
	 * @return string
	 */
	protected function findSessionId()
	{
		return sha1(uniqid(__CLASS__, true));
	}
	
	/**
	 *
	 */
	protected function readSessionData()
	{
		$tableName = self::TABLE_NAME;
		$sql = "SELECT 
					s.id as id, 
					s.name as name, 
					s.dateCreated as dateCreated, 
					s.data as data 
				FROM 
					{$tableName} s
				WHERE s.id = ? AND s.name = ?";

		$sessionName = $this->getSessionName();
		$sessionId = $this->getSessionId();
		$connection = $this->getDatabaseConnection();

		$sessionRecords = $connection->fetchAll($sql, array($sessionId, $sessionName));

		if (count($sessionRecords) > 1) {
			\Log::warn("Multiple records found for session $sessionName=$sessionId: ", $sessionRecords);
			$this->destroy();
			$sessionRecords = array();
		}
		
		$sessionRecord = reset($sessionRecords);

		if (empty($sessionRecord)) {
			$this->newRecord = true;
		}
		$sessionData = array();

		if ( ! empty($sessionRecord) && isset($sessionRecord['data'])) {
			$this->originalDataHash = md5($sessionRecord['data']);
			$sessionData = unserialize($sessionRecord['data']);
		}

		return $sessionData;
	}
	
	/**
	 *
	 */
	public function destroy()
	{
		$tableName = self::TABLE_NAME;

		$sql = "DELETE FROM {$tableName} WHERE id = ?";

		$connection = $this->getDatabaseConnection();
		$connection->executeQuery($sql, array($this->sessionId));

		parent::destroy();
	}
	
	/**
	 *
	 */
	public function close() 
	{
		if ($this->getSessionStatus() !== self::SESSION_STARTED) {
			return;
		}
		
		parent::close();
		
		$tableName = self::TABLE_NAME;
		
		if ($this->isInsertRequired()) {
			$sql = "INSERT INTO {$tableName} (id, name, dateCreated, data)
							VALUES(?, ?, NOW(), ?)";
			
			$connection = $this->getDatabaseConnection();
			$connection->executeQuery($sql, array(
				$this->sessionId,
				$this->sessionName,
				serialize($this->sessionData),
			));
		} 
		else if ($this->isUpdateRequired()) {
			
			$sql = "UPDATE
						{$tableName}
					SET	
						name = ?,
						data = ?
					WHERE
						id = ?";
						
			$connection = $this->getDatabaseConnection();
			$connection->executeQuery($sql, array(
				$this->sessionName,
				serialize($this->sessionData),
				$this->sessionId
			));
		}
	}
	
	/**
	 * @return boolean
	 */
	private function isInsertRequired()
	{
		if ($this->newRecord && ! empty($this->sessionData)) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return boolean
	 */
	private function isUpdateRequired()
	{
		$hash = md5(serialize($this->sessionData));
		
		if ($hash !== $this->originalDataHash) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return \Doctrine\DBAL\Connection
	 */
	private function getDatabaseConnection()
	{
		if (is_null($this->connection)) {
			$this->connection = ObjectRepository::getEntityManager($this)
					->getConnection();
		}
		
		return $this->connection;
	}

}
