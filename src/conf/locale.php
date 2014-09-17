<?php

use Supra\Locale;
use Supra\ObjectRepository\ObjectRepository;


/**
 
bin/supra su:locale:add --context=unified en_LV --title=English --country=Latvia --active=1
bin/supra su:locale:set_property --context=unified en_LV --name=flag --value=gb
bin/supra su:locale:set_property --context=unified en_LV --name=language --value=en

bin/supra su:locale:add --context=unified lv_LV --title=Latvian --country=Latvia  --active=1
bin/supra su:locale:set_property --context=unified lv_LV --name=flag --value=lv
bin/supra su:locale:set_property --context=unified lv_LV --name=language --value=lv

bin/supra su:locale:add --context=unified ru_RU --title=Russian --country=Russia  --active=1
bin/supra su:locale:set_property --context=unified ru_RU --name=flag --value=ru
bin/supra su:locale:set_property --context=unified ru_RU --name=language --value=ru

bin/supra su:locale:update --context=unified lv_LV --default

 **/

//{
//	$localeManager = new Locale\DatabaseBackedLocaleManager('unified');
//	
//	$localeManager->setCurrent($localeManager->getDefaultLocale());
//	
//	$localeManager->addDetector(new Locale\Detector\PathLocaleDetector());
//	$localeManager->addDetector(new Locale\Detector\CookieDetector());
//
//	$localeManager->addStorage(new Locale\Storage\CookieStorage());
//	$localeManager->processInactiveLocales();
//
//	ObjectRepository::setDefaultLocaleManager($localeManager);
//}
