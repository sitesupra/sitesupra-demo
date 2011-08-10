<?php

$log = Supra\Log\Log::getInstance();

// Set custom bootstrap writer
$bootstrapWriter = new Supra\Log\Writer\FileWriter();
$bootstrapWriter->setName('Bootstrap');
$bootstrapWriter->addFilter(new Supra\Log\Filter\LevelFilter(array('level' => \Log::WARN)));
Supra\Log\Log::setBootstrapWriter($bootstrapWriter);

// Configure Log4j writer
$log4j = new Supra\Log\Writer\FileWriter();
$log4j->setName('Supra7');
$log4j->addFilter(new Supra\Log\Filter\LevelFilter(array('level' => \Log::WARN)));
$log->addWriter(Supra\Log\Log::LOGGER_SUPRA, $log4j);
$log->addWriter(Supra\Log\Log::LOGGER_PHP, $log4j);
$log->addWriter(Supra\Log\Log::LOGGER_APPLICATION, $log4j);

// Configure FirePhp log writer only for local IP addresses
//$ipFilter = new Supra\Log\Filter\IpFilter(array('range' => '127.*,10.*'));
//$firePhp = new Supra\Log\Writer\FirePhpWriter();
//$firePhp->addFilter($ipFilter);
////$firePhp->addFilter(new Supra\Log\Filter\Level(array('level' => \Log::WARN)));
//$firePhp->setName('Supra7');
//$log->addWriter(Supra\Log\Log::LOGGER_SUPRA, $firePhp);
//$log->addWriter(Supra\Log\Log::LOGGER_PHP, $firePhp);
//$log->addWriter(Supra\Log\Log::LOGGER_APPLICATION, $firePhp);
