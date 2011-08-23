<?php

use Supra\ObjectRepository\ObjectRepository;

$userProvider = new \Supra\User\UserProvider();

$emailVailidation = new Supra\User\Validation\EmailValidation();

$userProvider->addValidationFilter($emailVailidation);

ObjectRepository::setDefaultUserProvider($userProvider);