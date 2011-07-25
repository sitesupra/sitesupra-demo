<?php

$fileStorage = Supra\FileStorage\FileStorage::getInstance();

$fileStorage->setInternalPath('files');
$fileStorage->setExternalPath('files');

$extensionFilter = new Supra\FileStorage\UploadFilter\ExtensionUploadFilter();
$extensionFilter->setMode(Supra\FileStorage\UploadFilter\ExtensionUploadFilter::MODE_WHITELIST);
$extensionFilter->addItems(array('gif','png','jpg','jpeg'));

$fileStorage->addUploadFilter($extensionFilter);

?>
