<?php

use Supra\ObjectRepository\ObjectRepository;

$ini = ObjectRepository::getIniConfigurationLoader('');
$cmsUrl = $ini->getValue('cms', 'url', false);

if ( ! empty($cmsUrl)) {
	define('SUPRA_CMS_URL', $cmsUrl);
}
