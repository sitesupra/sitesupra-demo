<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\User;
use Supra\User\Validation\EmailValidation;
use Supra\Authentication\Adapter\HashAdapter;
use Supra\Password;

$userProvider = new User\UserProvider();
//$userProvider->setRemoteApiEndpointId('portal');
		
$userProvider->addValidationFilter(new EmailValidation());

$passwordPolicy = new Password\PasswordPolicy;

// Password min/max length validator
$passwordPolicy->addValidationFilter(new Password\Validation\LengthValidation(5));

// Password re-use 
$passwordPolicy->addValidationFilter(new Password\Validation\PasswordReuseValidation(3));
//$passwordPolicy->addValidationFilter(new Password\Validation\CharacterSetStrengthValidation(true, true, true));
//$passwordPolicy->addValidationFilter(new Password\Validation\LetterCaseValidation(true, true));

// Expiration period is defined as an ISO 8601 string
// see: http://en.wikipedia.org/wiki/Iso8601#Durations
$passwordPolicy->setPasswordExpirationPeriod('P90D');

$userProvider->setPasswordPolicy($passwordPolicy);

$authAdapter = new HashAdapter();

$ini = ObjectRepository::getIniConfigurationLoader('');
$defaultDomain = $ini->getValue('cms_authentication', 'default_domain', '');
$authAdapter->setDefaultDomain($defaultDomain);

$userProvider->setAuthAdapter($authAdapter);

// This is provider for CMS
//ObjectRepository::setUserProvider('Supra\Cms', $userProvider);

// Experimental: set by ID
ObjectRepository::setUserProvider('#cms', $userProvider);

$frontendUserProvider = new User\DummyAPIUserProvider();
$frontendUserProvider->addValidationFilter(new EmailValidation());

$authAdapter = new HashAdapter();
$defaultDomain = $ini->getValue('frontend_authentication', 'default_domain', '');
$authAdapter->setDefaultDomain($defaultDomain);

$frontendUserProvider->setAuthAdapter($authAdapter);

ObjectRepository::setUserProvider('Supra\Controller\Pages\PageController', $frontendUserProvider);
ObjectRepository::setUserProvider('Project\Blocks\Login\LoginPreFilterController', $frontendUserProvider);

// Experimental: added extra rules for controllers
ObjectRepository::setUserProvider('Supra\Cms\CmsController', $userProvider);
ObjectRepository::setUserProvider('Supra\Cms\InternalUserManager\Restore\RestoreController', $userProvider);
ObjectRepository::setUserProvider('Supra\Cms\AuthenticationPreFilterController', $userProvider);
ObjectRepository::setUserProvider('Supra\User\Command\CreateUserCommand', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\SampleAuthenticationController', $userProvider);
ObjectRepository::setUserProvider('Project\SampleAuthentication\SampleAuthenticationPreFilter', $userProvider);
ObjectRepository::setUserProvider('Project\SocialMedia\SocialMediaController', $userProvider);
ObjectRepository::setUserProvider('Project\CmsRemoteLogin\Controller', $userProvider);
