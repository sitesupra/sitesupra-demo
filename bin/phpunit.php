<?php

// Supra starting
define('SUPRA_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require_once SUPRA_PATH . 'library/Supra/bootstrap.php';

// Supra test namespace registering
$testsNamespace = new \Supra\Loader\NamespaceRecord('Supra\\Tests', SUPRA_PATH . 'tests/library/Supra');
$loader->registerNamespace($testsNamespace);

// PHPUnit bootstrap
if (extension_loaded('xdebug')) {
	xdebug_disable();
}

require 'PHPUnit/TextUI/Command.php';

define('PHPUnit_MAIN_METHOD', 'PHPUnit_TextUI_Command::main');

PHPUnit_TextUI_Command::main();