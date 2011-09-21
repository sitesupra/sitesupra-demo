<?php

use Supra\ObjectRepository\ObjectRepository;
use Supra\Authorization\AuthorizationProvider;

$authorizationProvider = new AuthorizationProvider(
	ObjectRepository::getEntityManager('Supra\Cms'),
	array(
		'class_table_name'         => 'acl_classes',
		'entry_table_name'         => 'acl_entries',
		'oid_table_name'           => 'acl_object_identities',
		'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
		'sid_table_name'           => 'acl_security_identities',
	)
);

ObjectRepository::setDefaultAuthorizationProvider($authorizationProvider);
