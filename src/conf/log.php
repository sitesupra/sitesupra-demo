<?php

$chainWriter = new Supra\Log\Writer\ChainWriter();

// Set custom bootstrap writer
$writer = new Supra\Log\Writer\FileWriter();
$writer->setName('Supra7');
$writer->addFilter(new Supra\Log\Filter\LevelFilter($ini['log']));

$chainWriter->addWriter($writer);

$ini['log']['level'] = 'debug';
$writer = new Supra\Log\Writer\FileWriter(array('file' => 'debug.log'));
$writer->setName('Supra7 DEBUG');
$writer->addFilter(new Supra\Log\Filter\LevelFilter($ini['log']));

$chainWriter->addWriter($writer);

// Configure FirePhp log writer only for local IP addresses
//$ipFilter = new Supra\Log\Filter\IpFilter(array('range' => '127.*,10.*'));
//$firePhp = new Supra\Log\Writer\FirePhpWriter();
//$firePhp->addFilter($ipFilter);
////$firePhp->addFilter(new Supra\Log\Filter\Level(array('level' => \Log::WARN)));
//$firePhp->setName('Supra7');

Supra\ObjectRepository\ObjectRepository::setDefaultLogger($chainWriter);
