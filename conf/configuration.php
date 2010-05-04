<?php

$log = \Log::getInstance();

// Set custom bootstrap writer
$bootstrapWriter = new \Supra\Log\Writer\Log4j();
$bootstrapWriter->addFilter(new \Supra\Log\Filter\Level(array('level' => \Log::DEBUG)));
$log->setBootstrapWriter($bootstrapWriter);

// Configure Log4j writer
$log4j = new Supra\Log\Writer\Log4j();
$log->addWriter(\Supra\Log\Logger::LOGGER_SUPRA, $log4j);
$log->addWriter(\Supra\Log\Logger::LOGGER_PHP, $log4j);
$log->addWriter(\Supra\Log\Logger::LOGGER_APPLICATION, $log4j);

// Configure FirePhp log writer only for local IP addresses
$ipFilter = new \Supra\Log\Filter\Ip(array('range' => '127.*,10.*'));
$firePhp = new Supra\Log\Writer\FirePhp();
$firePhp->addFilter($ipFilter);
$log->addWriter(Supra\Log\Logger::LOGGER_SUPRA, $firePhp);
$log->addWriter(Supra\Log\Logger::LOGGER_PHP, $firePhp);
$log->addWriter(Supra\Log\Logger::LOGGER_APPLICATION, $firePhp);