<?php
use Supra\Locale;
use Supra\ObjectRepository\ObjectRepository;

$localeManager = new Locale\LocaleManager();

$localeManager->addDetector(new Locale\Detector\PathLocaleDetector);
$localeManager->addDetector(new Locale\Detector\CookieDetector);

$localeManager->addStorage(new Locale\Storage\CookieStorage);


/* English */
$locale = new Locale\Locale();
$locale->setId('en');
$locale->setTitle('English');
$locale->setCountry('Latvia');
$localeManager->add($locale);
/* Latvian */
$locale = new Locale\Locale();
$locale->setId('lv');
$locale->setTitle('Latvian');
$locale->setCountry('Latvia');
$localeManager->add($locale);
/* Russian */
$locale = new Locale\Locale();
$locale->setId('ru');
$locale->setTitle('Russian');
$locale->setCountry('Russia');
$localeManager->add($locale);


$localeManager->setCurrent('en');
ObjectRepository::setDefaultLocaleManager($localeManager);
