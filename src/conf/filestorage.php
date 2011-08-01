<?php

$fileStorage = Supra\FileStorage\FileStorage::getInstance();

// FIXME: should doctrine entity manager be as file stogare parameter?
//$fileStorage->setDoctrineEntityManager(\Supra\Database\Doctrine::getInstance()->getEntityManager());
$fileStorage->setInternalPath('files');
$fileStorage->setExternalPath('files');

$extensionFilter = new Supra\FileStorage\UploadFilter\ExtensionUploadFilter();
$extensionFilter->setMode(Supra\FileStorage\UploadFilter\ExtensionUploadFilter::MODE_WHITELIST);
$extensionFilter->addItems(array('gif','png','jpg','jpeg'));

$fileNameFilter = new Supra\FileStorage\UploadFilter\FileNameUploadFilter();

$existingFileNameFilter = new Supra\FileStorage\UploadFilter\ExistingFileNameUploadFilter();

// file filters
$fileStorage->addFileUploadFilter($extensionFilter);
$fileStorage->addFileUploadFilter($fileNameFilter);
$fileStorage->addFileUploadFilter($existingFileNameFilter);

// folder filters
$fileStorage->addFolderUploadFilter($fileNameFilter);
$fileStorage->addFolderUploadFilter($existingFileNameFilter);