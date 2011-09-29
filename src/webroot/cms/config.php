<?php

namespace Supra\Cms;

// Bind to URL /dc
$routerConfiguration = new \Supra\Router\Configuration\RouterConfiguration();
$routerConfiguration->url = '/cms';
$routerConfiguration->controller = 'Supra\Cms\CmsController';


$controllerConfiguration = new \Supra\Controller\Configuration\ControllerConfiguration();
$controllerConfiguration->router = $routerConfiguration;
$controllerConfiguration->configure();



$contentManagerConfiguration = new \Supra\Cms\ApplicationConfiguration();
$contentManagerConfiguration->id = 'content';
$contentManagerConfiguration->title = 'Pages';
$contentManagerConfiguration->path = '/cms/content-manager';
$contentManagerConfiguration->icon = '/cms/lib/supra/img/apps/content';
$contentManagerConfiguration->applicationNamespace = '\Supra\Cms\ContentManager';
$contentManagerConfiguration->authorizationAccessPolicyClass = '\Supra\Cms\ContentManager\ContentManagerAuthorizationAccessPolicy';
$contentManagerConfiguration->configure();



$userManagerConfiguration = new \Supra\Cms\ApplicationConfiguration();
$userManagerConfiguration->id = 'internal-user-manager';
$userManagerConfiguration->title = 'Backoffice users';
$userManagerConfiguration->path = '/cms/internal-user-manager';
$userManagerConfiguration->icon = '/cms/lib/supra/img/apps/internal-user';
$userManagerConfiguration->applicationNamespace = '\Supra\Cms\InternalUserManager';
$userManagerConfiguration->authorizationAccessPolicyClass = '\Supra\Authorization\AccessPolicy\AuthorizationAllOrNoneAccessPolicy';
$userManagerConfiguration->configure();



$bannerManagerConfiguration = new \Supra\Cms\ApplicationConfiguration();
$bannerManagerConfiguration->id = 'banner-manager';
$bannerManagerConfiguration->title = 'Banners';
$bannerManagerConfiguration->path = '/cms/banner-manager';
$bannerManagerConfiguration->icon = '/cms/lib/supra/img/apps/banner';
$bannerManagerConfiguration->applicationNamespace = '\Supra\Cms\BannerManager';
$bannerManagerConfiguration->authorizationAccessPolicyClass = '\Supra\Authorization\AccessPolicy\AuthorizationAllOrNoneAccessPolicy';
$bannerManagerConfiguration->configure();



$mediaLibraryManagerConfiguration = new \Supra\Cms\ApplicationConfiguration();
$mediaLibraryManagerConfiguration->id = 'media-library';
$mediaLibraryManagerConfiguration->title = 'Media Library';
$mediaLibraryManagerConfiguration->path = '/cms/media-library';
$mediaLibraryManagerConfiguration->icon = '/cms/lib/supra/img/apps/media_library';
$mediaLibraryManagerConfiguration->applicationNamespace = '\Supra\Cms\MediaLibrary';
$mediaLibraryManagerConfiguration->authorizationAccessPolicyClass = '\Supra\Cms\MediaLibrary\MediaLibraryAuthorizationAccessPolicy';
$mediaLibraryManagerConfiguration->configure();


$newsApplicationConfiguration = new \Supra\Controller\Pages\Configuration\PageApplicationConfiguration();
$newsApplicationConfiguration->id = 'news';
$newsApplicationConfiguration->className = 'Supra\Controller\Pages\News\NewsApplication';
$newsApplicationConfiguration->configure();
