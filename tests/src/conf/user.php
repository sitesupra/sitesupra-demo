<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Authentication\Adapters\HashAdapter;
use Supra\User\UserProvider;
use Supra\User\Validation\EmailValidation;

$userProvider = new UserProvider();
$entityManager = ObjectRepository::getEntityManager('Supra\Tests');
$userProvider->setEntityManager($entityManager);

$emailVailidation = new EmailValidation();
$emailVailidation->setEntityManager($entityManager);
$userProvider->addValidationFilter($emailVailidation);
$authAdapter = new HashAdapter();
$userProvider->setAuthAdapter($authAdapter);

ObjectRepository::setUserProvider('Supra\Tests', $userProvider);
