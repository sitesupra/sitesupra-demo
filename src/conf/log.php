<?php

// Set custom bootstrap writer
$writer = new Supra\Log\Writer\FileWriter();
$writer->setName('Supra7');
$writer->addFilter(new Supra\Log\Filter\LevelFilter(array('level' => Supra\Log\LogEvent::DEBUG)));

Supra\ObjectRepository\ObjectRepository::setDefaultLogger($writer);

// Configure Log4j writer
//$log4j = new Supra\Log\Writer\FileWriter();
//$log4j->setName('Supra7');
//$log4j->addFilter(new Supra\Log\Filter\LevelFilter(array('level' => \Log::WARN)));

// Configure FirePhp log writer only for local IP addresses
//$ipFilter = new Supra\Log\Filter\IpFilter(array('range' => '127.*,10.*'));
//$firePhp = new Supra\Log\Writer\FirePhpWriter();
//$firePhp->addFilter($ipFilter);
////$firePhp->addFilter(new Supra\Log\Filter\Level(array('level' => \Log::WARN)));
//$firePhp->setName('Supra7');
