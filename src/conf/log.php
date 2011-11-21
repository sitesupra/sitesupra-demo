<?php

use Supra\Log\LogEvent;
use Supra\Log\Writer;
use Supra\Log\Filter;
use Supra\ObjectRepository\ObjectRepository;

/*
 * Default log
 */
$defaultWriter = new Writer\FileWriter();
$defaultWriter->setName('Supra7');

$ini = ObjectRepository::getIniConfigurationLoader('');
$logParams = $ini->getSection('log');

$defaultWriter->addFilter(new Filter\LevelFilter($logParams));

Supra\ObjectRepository\ObjectRepository::setDefaultLogger($defaultWriter);

/*
 * SQL statement logger
 */
$chainWriter = new Writer\ChainWriter();
$chainWriter->addWriter($defaultWriter);

$sqlWriter = new Writer\FileWriter(array('file' => 'sql.log'));
$sqlWriter->setName('SQL');

// Info level filter skips SELECT statements
$sqlWriter->addFilter(new Filter\LevelFilter(array('level' => LogEvent::INFO)));

$chainWriter->addWriter($sqlWriter);

ObjectRepository::setLogger('Supra\Log\Logger\SqlLogger', $chainWriter);

/*
 * Audit log
 */
$auditWriter = new Writer\AuditLogWriter(array('file' => 'audit.log'));
ObjectRepository::setDefaultAuditLogger($auditWriter);

