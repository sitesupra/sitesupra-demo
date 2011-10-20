<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;
use Supra\User\Entity\Group;

$authorizationProvider = new AuthorizationProvider();

Group::registerPermissions($authorizationProvider);

ObjectRepository::setCallerParent($authorizationProvider, '#cms');
ObjectRepository::setAuthorizationProvider('Supra\Cms', $authorizationProvider);
