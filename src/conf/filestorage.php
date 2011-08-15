<?php

use Supra\ObjectRepository\ObjectRepository;

$fileStorage = Supra\FileStorage\FileStorage::getInstance();

// FIXME: should doctrine entity manager be as file stogare parameter?
//$fileStorage->setDoctrineEntityManager(\Supra\Database\Doctrine::getInstance()->getEntityManager());
$fileStorage->setInternalPath('files');
$fileStorage->setExternalPath('files');

$extensionFilter = new Supra\FileStorage\Validation\ExtensionUploadFilter();
$extensionFilter->setMode(Supra\FileStorage\Validation\ExtensionUploadFilter::MODE_WHITELIST);
$extensionFilter->addItems(
		array('gif', 'png', 'jpg', 'jpeg', 'doc', 'docx', 'pdf', 'xls', 'xlsx', 'txt'));

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