<?php

$cli = \Supra\Console\Application::getInstance();

//$cli->addCronJob('su:schema:update', 
//		new \Supra\Console\Cron\Period\EveryHourPeriod('30'));

$cli->addCronJob('su:pages:process_scheduled', 
	new \Supra\Console\Cron\Period\EveryIntervalPeriod('5 minutes'));

$cli->addCronJob('su:search:run_indexer', 
	new \Supra\Console\Cron\Period\EveryIntervalPeriod('1 minutes'));

$cli->addCronJob('su:seo:generate_sitemap', 
	new \Supra\Console\Cron\Period\EveryIntervalPeriod('10 minutes'));
