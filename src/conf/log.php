<?php

$log = Supra\Log\Logger::getInstance();

// Set custom bootstrap writer
$bootstrapWriter = new Supra\Log\Writer\File();
$bootstrapWriter->setName('Bootstrap');
$bootstrapWriter->addFilter(new Supra\Log\Filter\Level(array('level' => \Log::DEBUG)));
Supra\Log\Logger::setBootstrapWriter($bootstrapWriter);

// Configure Log4j writer
$log4j = new Supra\Log\Writer\File();
$log4j->setName('Supra7');
//$log4j->addFilter(new Supra\Log\Filter\Level(array('level' => \Log::INFO)));
$log->addWriter(Supra\Log\Logger::LOGGER_SUPRA, $log4j);
$log->addWriter(Supra\Log\Logger::LOGGER_PHP, $log4j);
$log->addWriter(Supra\Log\Logger::LOGGER_APPLICATION, $log4j);

// Configure FirePhp log writer only for local IP addresses
//$ipFilter = new Supra\Log\Filter\Ip(array('range' => '127.*,10.*'));
//$firePhp = new Supra\Log\Writer\FirePhp();
//$firePhp->addFilter($ipFilter);
////$firePhp->addFilter(new Supra\Log\Filter\Level(array('level' => \Log::WARN)));
//$firePhp->setName('Supra7');
//$log->addWriter(Supra\Log\Logger::LOGGER_SUPRA, $firePhp);
//$log->addWriter(Supra\Log\Logger::LOGGER_PHP, $firePhp);
//$log->addWriter(Supra\Log\Logger::LOGGER_APPLICATION, $firePhp);
