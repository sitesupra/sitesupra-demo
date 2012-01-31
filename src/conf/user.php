<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\User;
use Supra\User\Validation\EmailValidation;
use Supra\Authentication\Adapter\HashAdapter;

$userProvider = new User\UserProvider();
$userProvider->addValidationFilter(new EmailValidation());

$authAdapter = new HashAdapter();

$ini = ObjectRepository::getIniConfigurationLoader('');
$defaultDomain = $ini->getValue('cms_authentication', 'default_domain', '');
$authAdapter->setDefaultDomain($defaultDomain);

$userProvider->setAuthAdapter($authAdapter);

// This is provider for CMS
//ObjectRepository::setUserProvider('Supra\Cms', $userProvider);

// Experimental: set by ID
ObjectRepository::setUserProvider('#cms', $userProvider);

// Experimental: added extra rules for controllers
ObjectRepository::setUserProvider('Supra\Cms\CmsController', $userProvider);
ObjectRepository::setUserProvider('Supra\Cms\InternalUserManager\Restore\RestoreController', $userProvider);
ObjectRepository::setUserProvider('Supra\Cms\AuthenticationPreFilterController', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\AuthenticateController', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\AuthenticatePreFilterController', $userProvider);
ObjectRepository::setUserProvider('Project\SocialMedia\SocialMediaController', $userProvider);
