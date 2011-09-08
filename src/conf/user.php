<?php

use Supra\ObjectRepository\ObjectRepository;
use Project\AutoregisterAuthenticationAdapter\AutoregisterAuthenticationAdapter;
use Supra\User\UserProvider;
use Supra\User\Validation\EmailValidation;

$userProvider = new UserProvider();

$emailVailidation = new EmailValidation();
$userProvider->addValidationFilter($emailVailidation);
$authAdapter = new AutoregisterAuthenticationAdapter();
$userProvider->setAuthAdapter($authAdapter);

ObjectRepository::setDefaultUserProvider($userProvider);
