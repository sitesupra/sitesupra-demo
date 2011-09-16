<?php
use Supra\Locale;
use Supra\ObjectRepository\ObjectRepository;

$localeManager = new Locale\LocaleManager();

$localeManager->addDetector(new Locale\Detector\PathLocaleDetector);
$localeManager->addDetector(new Locale\Detector\CookieDetector);

$localeManager->addStorage(new Locale\Storage\CookieStorage);


/* English | Latvia */
$locale = new Locale\Locale();
$locale->setId('en_LV');
$locale->setTitle('English');
$locale->setCountry('Latvia');
$locale->addProperty('flag', 'gb');
$localeManager->add($locale);
/* Latvian | Latvia */
$locale = new Locale\Locale();
$locale->setId('lv_LV');
$locale->setTitle('Latvian');
$locale->setCountry('Latvia');
$locale->addProperty('flag', 'lv');
$localeManager->add($locale);
/* Russian | Russia */
$locale = new Locale\Locale();
$locale->setId('ru_RU');
$locale->setTitle('Russian');
$locale->setCountry('Russia');
$locale->addProperty('flag', 'ru');
$localeManager->add($locale);


$localeManager->setCurrent('en_LV');
ObjectRepository::setDefaultLocaleManager($localeManager);
