<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authentication\Adapter\HashAdapter;
use Supra\User\UserProvider;
use Supra\User\Validation\EmailValidation;

$userProvider = new UserProvider();
$userProvider->addValidationFilter(new EmailValidation());
$userProvider->setAuthAdapter(new HashAdapter());

ObjectRepository::setUserProvider('Supra\Tests', $userProvider);

$testEntityManager = ObjectRepository::getEntityManager('Supra\Tests');

//TODO: Should somehow assign Tests database connection for this user provider instance
//ObjectRepository::setEntityManager($userProvider, $testEntityManager);
