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
	 * Generates random session id
	 * 
	 * @return string
	 */
	public static function generateId()
	{
		return sha1(uniqid(null, true));
	}
	
	/**
	 *
	 */
	public function start() 
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
		
		if ( ! empty($sessionId)) {
	
			$connection = $this->getDatabaseConnection();

			$sessionRecord = $connection->executeQuery($sql, array($sessionId, $sessionName));
			
			// FIXME: !
			if (empty($sessionRecord)) {
				$this->newRecord = true;
			}
			
			if ( ! empty($sessionRecord) && isset($sessionRecord['data'])) {
				
				$this->originalDataHash = md5($sessionRecord['data']);
				
				$this->sessionData = unserialize($sessionRecord['data']);
			}
		} else {
			$sessionId = self::generateId();
			$this->newRecord = true;
		}
		
		$this->setSessionId($sessionId);
	
		$this->sessionStatus = self::SESSION_STARTED;

		if ( ! isset($this->sessionData[self::SESSION_LAST_ACTIVITY_OFFSET])) {
			$this->sessionData[self::SESSION_LAST_ACTIVITY_OFFSET] = time();
		}
			
		$this->checkSessionExpire();
	}
	
	/**
	 *
	 */
	public function checkSessionExpire()
	{
		if (empty($this->expirationTime)) {
			return false;
		}
		
		$expireTime = $this->sessionData[self::SESSION_LAST_ACTIVITY_OFFSET] + $this->expirationTime;
		
		if ($expireTime < time()) {
			$this->clear();
			
			$tableName = self::TABLE_NAME;
			
			$sql = "DELETE FROM {$tableName} s WHERE s.id = ?";
			
			$connection = $this->getDatabaseConnection();
			$connection->executeQuery($sql, array($this->sessionId));
			
			$this->sessionId = null;
		}
	}
	
	/**
	 *
	 */
	public function close() 
	{
		if ($this->getSessionStatus() === self::SESSION_NOT_STARTED) {
			return;
		}
		
		parent::close();
		
		$tableName = self::TABLE_NAME;
		
		if ($this->isInsertRequired()) {
			$sql = "INSERT INTO {$tableName} s (s.id, s.name, s.dateCreated, s.data)
							VALUES(?, ?, NOW(), ?)";
			
			$connection = $this->getDatabaseConnection();
			$connection->executeQuery($sql, array(
				$this->sessionId,
				$this->sessionName,
				$this->sessionData,
			));
		} 
		else if ($this->isUpdateRequired()) {
			
			$sql = "UPDATE
						{$tableName} s
					SET	
						s.name = ?,
						s.data = ?
					WHERE
						s.id = ?";
						
			$connection = $this->getDatabaseConnection();
			$connection->executeQuery($sql, array(
				$this->sessionName,
				$this->sessionData,
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
