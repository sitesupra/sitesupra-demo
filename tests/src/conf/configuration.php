<?php

$ini = parse_ini_file(SUPRA_TESTS_CONF_PATH . 'supra.ini', true);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'user.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'filestorage.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'mailer.php';
