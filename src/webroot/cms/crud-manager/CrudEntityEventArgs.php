<?php

namespace Supra\Cms\CrudManager;

class CrudEntityEventArgs extends \Supra\Event\EventArgs
{
	/**
	 * @var \Supra\Database\Entity
	 */
	public $entity;
	
	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	public $entityManager;

	/**
	 * @var \Supra\Validator\FilteredInput
	 */
	public $input;
}