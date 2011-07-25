<?php

$fileStorage = Supra\FileStorage\FileStorage::getInstance();

$fileStorage->setInternalPath('files');
$fileStorage->setExternalPath('files');

$extensionFilter = new Supra\Validation\ExtensionUploadFilter();
$extensionFilter->setMode(Supra\Validation\ExtensionUploadFilter::MODE_WHITELIST);
$extensionFilter->addItem('gif','png','jpg','jpeg');

$fileStorage->addUploadFilter($extensionFilter);

?>
