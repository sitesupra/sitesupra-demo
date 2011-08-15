<?php

use Supra\ObjectRepository\ObjectRepository;

$fileStorage = new Supra\FileStorage\FileStorage();

// FIXME: should doctrine entity manager be as file stogare parameter?
//$fileStorage->setDoctrineEntityManager(\Supra\Database\Doctrine::getInstance()->getEntityManager());
$fileStorage->setInternalPath('files');
$fileStorage->setExternalPath('files');

$extensionFilter = new Supra\FileStorage\Validation\ExtensionUploadFilter();
$extensionFilter->setMode(Supra\FileStorage\Validation\ExtensionUploadFilter::MODE_BLACKLIST);
$extensionFilter->addItems(
		array('php', 'phtml', 'php3', 'php4', 'js', 'shtml', 
			'pl' ,'py', 'cgi', 'sh', 'asp', 'exe', 'bat'
		));

$fileNameFilter = new Supra\FileStorage\Validation\FileNameUploadFilter();

$existingFileNameFilter = new Supra\FileStorage\Validation\ExistingFileNameUploadFilter();

// file filters
$fileStorage->addFileUploadFilter($extensionFilter);
$fileStorage->addFileUploadFilter($fileNameFilter);
$fileStorage->addFileUploadFilter($existingFileNameFilter);

// folder filters
$fileStorage->addFolderUploadFilter($fileNameFilter);
$fileStorage->addFolderUploadFilter($existingFileNameFilter);

ObjectRepository::setDefaultFileStorage($fileStorage);