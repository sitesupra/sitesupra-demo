#!/usr/bin/env php
<?php

//echo shell_exec('php "' . __DIR__ . DIRECTORY_SEPARATOR . 'doctrine.php"');
$exitStrings = array('exit', 'quit', 'q');

$commonPart = null;

$commands = array (
  array (
    'command' => 'quit',
    'comment' => 'Exit',
  ),
  array (
    'command' => 'help',
    'comment' => 'Displays help for a command (?)',
  ),
  array (
    'command' => 'list',
    'comment' => 'Lists commands',
  ),
  array (
    'command' => 'dbal:import',
    'comment' => 'Import SQL file(s) directly to Database.',
  ),
  array (
    'command' => 'dbal:run-sql',
    'comment' => 'Executes arbitrary SQL directly from the command line.',
  ),
  array (
    'command' => 'orm:convert-d1-schema',
    'comment' => 'Converts Doctrine 1.X schema into a Doctrine 2.X schema.',
  ),
  array (
    'command' => 'orm:convert-mapping',
    'comment' => 'Convert mapping information between supported formats.',
  ),
  array (
    'command' => 'orm:ensure-production-settings',
    'comment' => 'Verify that Doctrine is properly configured for a production environment.',
  ),
  array (
    'command' => 'orm:generate-entities',
    'comment' => 'Generate entity classes and method stubs from your mapping information.',
  ),
  array (
    'command' => 'orm:generate-proxies',
    'comment' => 'Generates proxy classes for entity classes.',
  ),
  array (
    'command' => 'orm:generate-repositories',
    'comment' => 'Generate repository classes from your mapping information.',
  ),
  array (
    'command' => 'orm:info',
    'comment' => 'Show basic information about all mapped entities.',
  ),
  array (
    'command' => 'orm:run-dql',
    'comment' => 'Executes arbitrary DQL directly from the command line.',
  ),
  array (
    'command' => 'orm:validate-schema',
    'comment' => 'Validate that the mapping files.',
  ),
  array (
    'command' => 'orm:clear-cache:metadata',
    'comment' => 'Clear all metadata cache of the various cache drivers.',
  ),
  array (
    'command' => 'orm:clear-cache:query',
    'comment' => 'Clear all query cache of the various cache drivers.',
  ),
  array (
    'command' => 'orm:clear-cache:result',
    'comment' => 'Clear result cache of the various cache drivers.',
  ),
  array (
    'command' => 'orm:schema-tool:create',
    'comment' => 'Processes the schema and either create it directly on EntityManager Storage Connection or generate the SQL output.',
  ),
  array (
    'command' => 'orm:schema-tool:drop',
    'comment' => 'Processes the schema and either drop the database schema of EntityManager Storage Connection or generate the SQL output.',
  ),
  array (
    'command' => 'orm:schema-tool:update',
    'comment' => 'Processes the schema and either update the database schema of EntityManager Storage Connection or generate the SQL output.',
  ),
);

while (true) {
	echo 'doctrine>', $commonPart;
	$input = fopen('php://stdin', 'r');
	$arg = fgets($input);
	$emptyInput = false;
	if (trim($arg) == '') {
		$emptyInput = true;
	}

	$arg = $commonPart . $arg;
	$arg = trim($arg);

	$argArray = explode(' ', $arg, 2);
	$command = $argArray[0];

	$found = false;
	$possibilities = array();
	$commonPart = null;
	foreach ($commands as $test) {
		if ($test['command'] == $command) {
			$found = true;
			break;
		}
		if (strpos($test['command'], $command) === 0) {
			$possibilities[] = $test;
			if (is_null($commonPart)) {
				$commonPart = $test['command'];
			} elseif ($commonPart != '') {
				while (strpos($test['command'], $commonPart) !== 0) {
					$commonPart = substr($commonPart, 0, -1);
				}
			}
		}
	}

	if (in_array(strtolower($arg), $exitStrings)) {
		break;
	}

	if ( ! $found) {
		if ($emptyInput) {
			$commonPart = null;
			continue;
		}
		if (count($possibilities) > 1) {
			echo "Possible commands:\n";
			foreach ($possibilities as $possibility) {
				echo "  {$possibility['command']} - {$possibility['comment']}\n";
			}
		}
		if (empty($possibilities)) {
			echo "Such command was not found\n";
		}
	} else {
		echo "Executing...\n";
		echo shell_exec('php "' . __DIR__ . DIRECTORY_SEPARATOR . 'doctrine.php" ' . $arg);
		echo "\n";
	}
}

echo 'Bye';