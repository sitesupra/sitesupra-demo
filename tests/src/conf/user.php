<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authentication\Adapter\HashAdapter;
use Supra\User\UserProvider;
use Supra\User\Validation\EmailValidation;

$userProvider = new UserProvider();
$entityManager = ObjectRepository::getEntityManager('Supra\Tests');
$userProvider->setEntityManager($entityManager);

$userProvider->addValidationFilter(new EmailValidation());
$userProvider->setAuthAdapter(new HashAdapter());

ObjectRepository::setUserProvider('Supra\Tests', $userProvider);
