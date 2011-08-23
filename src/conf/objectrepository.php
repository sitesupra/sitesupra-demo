<?php

use Supra\ObjectRepository\ObjectRepository;

//ObjectRepository::setDefaultObject($object);
ObjectRepository::setDefaultEntityManager(
		\Supra\Database\Doctrine::getInstance()->getEntityManager());
ObjectRepository::setEntityManager("Supra\FileStorage", 
		\Supra\Database\Doctrine::getInstance()->getEntityManager());
ObjectRepository::setEntityManager("Supra\User", 
		\Supra\Database\Doctrine::getInstance()->getEntityManager());
