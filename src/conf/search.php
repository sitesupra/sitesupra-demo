<?php

use Supra\ObjectRepository\ObjectRepository;

use Supra\Controller\Pages\PageLocalizationIndexerQueue;

$pageLocalizationIndexerQueue = new PageLocalizationIndexerQueue();

ObjectRepository::setIndexerQueue('Supra\Controller\Pages', $pageLocalizationIndexerQueue);
