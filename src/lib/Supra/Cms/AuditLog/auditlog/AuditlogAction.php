<?php

namespace Supra\Cms\AuditLog\Auditlog;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\LogEvent;
use Supra\Database\Doctrine\Type\UtcDateTimeType;

class AuditlogAction extends CmsAction
{	
	
	/**
	 * @var Doctrine\DBAL\Connection
	 */
	protected $connection;
	
	/**
	 * @var array
	 */
	private $expectedInputs = array(
		'start_date', 'end_date', 'user_id', 'level', 'component'
	);
	
	
	public function loadAction()
	{
		$input = $this->getRequestInput();
		/* @var $input Supra\Request\RequestData */
		
		$limit = $this->getRequestParameter('resultsPerRequest');
		$offset =  $this->getRequestParameter('offset');
		
		$requestParams = array();
		
		foreach($this->expectedInputs as $key) {
			if ($this->hasRequestParameter($key)) {

				$requestParams[$key] = $input->get($key);
			}
		}
		
		// just to be sure, that end date is greater than start date
		// should be done at userside instead
		if ($this->hasRequestParameter('end_date') && $this->hasRequestParameter('start_date')) {
			$start = strtotime($this->getRequestParameter('start_date'));
			$end = strtotime($this->getRequestParameter('end_date'));

			if ($start >= $end) {
				throw new \Supra\Cms\Exception\CmsException(null, 'End date should be greater than start date');
			}
		}

		$response = $this->getAuditData($limit, $offset, $requestParams);
		
		$this->getResponse()
				->setResponseData($response);
	}
	
	protected function prepareConnection()
	{
		$ini = ObjectRepository::getIniConfigurationLoader($this);
		$config = $ini->getSection('database_audit_log', array());
		
		if (empty($config)) {
			$config = $ini->getSection('database');
		}
		
		$this->connection = \Doctrine\DBAL\DriverManager::getConnection($config);
		
		$tableName = \Supra\AuditLog\Writer\DatabaseAuditLogWriter::AUDIT_TABLE;
		
		// check, if audit table exists
		$query = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
		// FIXME: IF(EXISTS(statement)) didn't work
		$tableCount = $this->connection->fetchColumn($query, array($config['dbname'], $tableName));
		if (empty($tableCount) || $tableCount == 0) {
			throw new CmsException(null, 'There is no audit table in database');
		}
		
	}
	
	protected function convertResultSet($results)
	{
		$array = array();
		
		$config = \Supra\Cms\CmsApplicationConfiguration::getInstance();
		$appConfigs = $config->getArray();
		
		$components = array();
		foreach($appConfigs as $config) {
			/* @var $config ApplicationConfiguration */
			$components[$config->class] = $config->title;
		}
		
		$utcTimeZone = new \DateTimeZone('UTC');
		
		foreach($results as $result) {
			
			$time = UtcDateTimeType::staticConvertToPHPValue($result['datetime']);
			$time->setTimeZone($utcTimeZone);
			
			$array[] = array(
				'id'	=>	$result['id'],
				'component' => (isset($components[$result['component']]) ? $components[$result['component']] : $result['component']),
				'level'		=> LogEvent::$levels[strtoupper($result['level'])],
				'user'		=> $result['user'],
				'time'		=> $time->format('Y-m-d H:i:s e'),
				'subject'	=> $result['message'],
				// FIXME: hardcoded value - is bad value
				'icon' => '/cms/audit-log/images/datagrid/icon-audit-log.png',
			);
		}
		
		return $array;
	}
	
	protected function getAuditData($limit = 0, $offset = 0, $requestParams)
	{
		if (empty($this->connection)) {
			$this->prepareConnection();
		}
		
		$tableName = \Supra\AuditLog\Writer\DatabaseAuditLogWriter::AUDIT_TABLE;
		
		$params = $conditions = array();
		
		foreach($requestParams as $requestParameter => $value) {
			
			switch($requestParameter) {
				case 'level':
					$conditions[] = 'a.level = ?';
					
					$levels = LogEvent::$levels;
					$level = array_search($value, $levels);
					
					$params[] = $level;
					break;
				
				case 'start_date':
					$conditions[] = 'a.datetime >= ?';
					$params[] = $value;
					break;
				
				case 'end_date':
					$conditions[] = 'a.datetime <= ?';
					$params[] = $value;
					break;
				
				case 'user_id':
					$conditions[] = 'a.user LIKE ?';
					$params[] = $value . '%';
					break;
				
				case 'component':
					$conditions[] = 'a.component = ?';
					$params[] = $value;
					break;
				
			}
			
		}
		
		$limitCondition = null;
		if ( ! is_null($limit) && ! is_null($offset)) {
			$limitCondition = "LIMIT {$offset},{$limit}";
		}
		
		$whereCondition = null;
		if ( ! empty($conditions)) {
			$whereCondition = 'WHERE ' . implode(' AND ', $conditions);
		}
		
		$countQuery = "SELECT COUNT(*) FROM {$tableName} a {$whereCondition} ";
		$total = $this->connection->fetchColumn($countQuery, $params);
		
		$results = array();
		if ($total > 0) {
			$query = "SELECT a.id, a.level, a.component, a.message, a.user, a.datetime FROM {$tableName} a {$whereCondition} ORDER BY a.datetime DESC " . ( ! empty($limitCondition) ? $limitCondition : '');;
			$results = $this->connection->fetchAll($query, $params);
		}
		
		$auditData = array(
			'offset' => $offset,
			'total' => $total,
			'results' => $this->convertResultSet($results),
		);
		
		return $auditData;
	}
	
}