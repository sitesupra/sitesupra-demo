<?php

$helperSet = null;

require_once __DIR__ . '/cli-config.php';

\Doctrine\ORM\Tools\Console\ConsoleRunner::run($helperSet);
