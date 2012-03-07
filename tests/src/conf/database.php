<?php

$test = new Supra\Tests\Database\Configuration\TestEntityManagerConfiguration();

$test->name = '#tests';
$test->objectRepositoryBindings = array('Supra\Tests');

// Prefix all tables
$test->tableNamePrefix = 'test_';
$test->tableNamePrefixNamespace = '';

$test->entityLibraryPaths = array(
	'Supra/Controller/Pages/Entity/',
	'Supra/FileStorage/Entity/',
	'Supra/User/Entity/',
	'Supra/Search/Entity',
	'Supra/Mailer/MassMail/Entity',
);

$test->entityPaths = array(
	SUPRA_TESTS_LIBRARY_PATH . 'Supra/NestedSet/Model',
	SUPRA_TESTS_LIBRARY_PATH . 'Supra/Search/Entity',
);

$test->configure();
