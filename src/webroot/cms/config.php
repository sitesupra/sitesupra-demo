<?php

namespace Supra\Cms;

// Bind to URL /dc
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/cms';
$routerConfiguration->controller = 'Supra\Cms\CmsController';

$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();

$applicationConfigution = \Supra\Cms\CmsApplicationConfiguration::getInstance();
$applicationConfigution->addConfiguration('content', "Pages", "/cms/content-manager", "/cms/lib/supra/img/apps/content");
$applicationConfigution->addConfiguration("internal-user-manager", "Backoffice users", "/cms/internal-user-manager", "/cms/lib/supra/img/apps/internal-user");
$applicationConfigution->addConfiguration("banner-manager", "Banners", "/cms/banner-manager", "/cms/lib/supra/img/apps/banner");
$applicationConfigution->addConfiguration("media-library", "Media Library", "/cms/media-library", "/cms/lib/supra/img/apps/media_library");