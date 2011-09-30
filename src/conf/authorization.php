<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;

$authorizationProvider = new AuthorizationProvider(
	ObjectRepository::getEntityManager('Supra\Cms')
);

ObjectRepository::setDefaultAuthorizationProvider($authorizationProvider);
