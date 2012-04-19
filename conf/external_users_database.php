<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Database\Configuration\EntityManagerConfiguration;

// Check if active
$ini = ObjectRepository::getIniConfigurationLoader('');
$active = $ini->getValue('external_user_database', 'active', false);
if ( ! $active) {
	return;
}

$configuration = new EntityManagerConfiguration();
$configuration->name = '#ExternalUsers';
$configuration->objectRepositoryBindings = array('Supra\User', 'Supra\Authorization');
$configuration->entityLibraryPaths = array(
	'Supra/User/Entity/'
);
$configuration->iniConfigurationSection = 'external_user_database';
$configuration->configure();

// TODO: how to configure NOT to load some listeners?
//$eventManager = new EventManager();
//$eventManager->addEventSubscriber(new TableNamePrefixer('su_'));
//$eventManager->addEventSubscriber(new TimestampableListener());
//
////$eventManager->addEventSubscriber(new Listener\VersionedAnnotationListener());
//
////$eventManager->addEventListener(array(Events::loadClassMetadata), new Listener\EntityRevisionListener());
//
//$eventManager->addEventSubscriber(new NestedSetListener());
