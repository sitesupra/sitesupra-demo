<?php

use Supra\Log\LogEvent;
use Supra\Log\Writer;
use Supra\Log\Filter;
use Supra\AuditLog\Writer as AuditWriter;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader\Loader;

$ini = ObjectRepository::getIniConfigurationLoader('');
$auditLogDbConnectionOptions = $ini->getSection('database_audit_log', array());

if (empty($auditLogDbConnectionOptions)) {
	$auditLogDbConnectionOptions = $ini->getSection('database');
}


/*
 * Default log
 */
$loggerClass = $ini->getValue('log', 'logger', 'Supra\Log\Writer\FileWriter');
$defaultWriter = Loader::getClassInstance($loggerClass, ObjectRepository::INTERFACE_LOGGER);
$defaultWriter->setName('Supra7');

$logParams = $ini->getSection('log', array('level' => 'warn'));

$defaultWriter->addFilter(new Filter\LevelFilter($logParams));

Supra\ObjectRepository\ObjectRepository::setDefaultLogger($defaultWriter);

/*
 * SQL statement logger
 */
$sqlLog = $ini->getValue('log', 'sql_log', false);

if ($sqlLog) {
	$sqlWriter = new Writer\FileWriter(array('file' => 'sql.log'));
	$sqlWriter->setName('SQL');

	// Info level filter skips SELECT statements
//	$sqlWriter->addFilter(new Filter\LevelFilter(array('level' => LogEvent::INFO)));

	ObjectRepository::setLogger('Supra\Log\Logger\SqlLogger', $sqlWriter);
}

/*
 * Audit log
 */
//Use file audit log
//$auditWriter = new AuditWriter\FileAuditLogWriter(array('file' => 'audit.log'));
//Use DB audit log
$auditWriter = new AuditWriter\DatabaseAuditLogWriter($auditLogDbConnectionOptions);

ObjectRepository::setDefaultAuditLogger($auditWriter);

/**
 * Cron tasks execution logger, for testing purposes
 */
$cronLog = $ini->getValue('log', 'cron_log', false);
if ($cronLog) {
	$cronWriter = new Writer\FileWriter(array('file' => 'cron.log'));
	$cronWriter->setName('Cron');

	ObjectRepository::setLogger('Supra\Console\Cron', $cronWriter);
}
