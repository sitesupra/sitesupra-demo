<?php

$fileStorage = Supra\FileStorage\FileStorage::getInstance();

// FIXME: should doctrine entity manager be as file stogare parameter?
//$fileStorage->setDoctrineEntityManager(\Supra\Database\Doctrine::getInstance()->getEntityManager());
$fileStorage->setInternalPath('files');
$fileStorage->setExternalPath('files');

$extensionFilter = new Supra\FileStorage\UploadFilter\ExtensionUploadFilter();
$extensionFilter->setMode(Supra\FileStorage\UploadFilter\ExtensionUploadFilter::MODE_WHITELIST);
$extensionFilter->addItems(array('gif','png','jpg','jpeg'));

$fileStorage->addUploadFilter($extensionFilter);

?>
