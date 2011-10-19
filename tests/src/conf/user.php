<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authentication\Adapter\HashAdapter;
use Supra\User\UserProvider;
use Supra\User\Validation\EmailValidation;

$userProvider = new UserProvider();
$userProvider->addValidationFilter(new EmailValidation());
$userProvider->setAuthAdapter(new HashAdapter());

// Assign user provider for tests namespace
ObjectRepository::setUserProvider('Supra\Tests', $userProvider);

// Assigns Tests database connection for this particular user provider instance
$testEntityManager = ObjectRepository::getEntityManager('Supra\Tests');
ObjectRepository::setEntityManager($userProvider, $testEntityManager);
