<?php

use Supra\ObjectRepository\ObjectRepository;

//ObjectRepository::setDefaultObject($object);
ObjectRepository::setDefaultEntityManager(
		\Supra\Database\Doctrine::getInstance()->getEntityManager());
