<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;

use Supra\User\Entity\Group;

$authorizationProvider = new AuthorizationProvider(
	ObjectRepository::getEntityManager('Supra\Cms')
);

Group::registerPermissions($authorizationProvider);

ObjectRepository::setDefaultAuthorizationProvider($authorizationProvider);
