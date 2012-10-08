<?php

namespace Supra\Cms\CrudManager;

/**
 * Crud controller
 */
class CrudEntityEventArgs extends \Supra\Event\EventArgs
{
	/**
	 * @var \Supra\Database\Entity
	 */
	public $entity;
	
	/**
	 *
	 * @var \Doctrine\ORM\EntityManager
	 */
	public $entityManager;
}