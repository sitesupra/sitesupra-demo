<?php

use Supra\ObjectRepository\ObjectRepository;

$userProvider = new \Supra\User\UserProvider();

$emailVailidation = new Supra\User\Validation\EmailValidation();
$userProvider->addValidationFilter($emailVailidation);

$authAdapter = new Supra\User\Authentication\Adapters\HashAdapter();
$userProvider->setAuthAdapter($authAdapter);

ObjectRepository::setDefaultUserProvider($userProvider);