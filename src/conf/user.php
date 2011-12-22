<?php

use Supra\ObjectRepository\ObjectRepository;
//use Project\AutoregisterAuthenticationAdapter\AutoregisterAuthenticationAdapter;
use Supra\Authentication\Adapter\HashAdapter;
use Supra\User\UserProvider;
use Supra\User\Validation\EmailValidation;

$userProvider = new UserProvider();
$userProvider->addValidationFilter(new EmailValidation());

$authAdapter = new HashAdapter();
$authAdapter->setDefaultDomain('supra7.vig');
//$userProvider->setAuthAdapter(new AutoregisterAuthenticationAdapter());
$userProvider->setAuthAdapter($authAdapter);

// This is provider for CMS
//ObjectRepository::setUserProvider('Supra\Cms', $userProvider);

// Experimental: set by ID
ObjectRepository::setUserProvider('#cms', $userProvider);

// Experimental: added extra rules for controllers
ObjectRepository::setUserProvider('Supra\Cms\AuthenticationPreFilterController', $userProvider);
ObjectRepository::setUserProvider('Supra\Cms\CmsController', $userProvider);
ObjectRepository::setUserProvider('Supra\Cms\InternalUserManager\Restore\RestoreController', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\AuthenticateController', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\AuthenticatePreFilterController', $userProvider);
