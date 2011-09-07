<?php

use Supra\ObjectRepository\ObjectRepository;

$userProvider = new \Supra\User\UserProvider();

$emailVailidation = new Supra\User\Validation\EmailValidation();
$userProvider->addValidationFilter($emailVailidation);

// For now it doesn't have components autoloader registered
require_once SUPRA_COMPONENT_PATH . 'dummy-authentication-adapter/DummyAuthenticationAdapter.php';

$authAdapter = new \Project\AuthenticationAdapter\DummyAuthenticationAdapter();
$userProvider->setAuthAdapter($authAdapter);

ObjectRepository::setDefaultUserProvider($userProvider);