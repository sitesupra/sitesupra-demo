<?php

$fileStorage = Supra\FileStorage\FileStorage::getInstance();

//TODO: Change to real paths
$fileStorage->setInternalPath('internal/path/goes/here');
$fileStorage->setExternalPath('external/path/goes/here');

?>
