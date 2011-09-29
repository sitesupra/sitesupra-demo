<?php

namespace Supra\Database\Console;

use \Symfony\Component\Console\Command\Command;
use \Supra\ObjectRepository\ObjectRepository;

/**
 * SchemaAbstractCommand
 *
 */
abstract class SchemaAbstractCommand extends Command
{
	/**
	 * Set of entity managers to work with
	 *
	 * @var array
	 */
	protected $entityManagers;

	/**
	 * Constructor
	 *
	 * @param string $name
	 */
	public function __construct($name = null)
	{
		parent::__construct($name);

		$this->entityManagers = array(
			'public' => ObjectRepository::getEntityManager(''),
			'draft' => ObjectRepository::getEntityManager('Supra\Cms'),
			'trash' => ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash'),
			'history' => ObjectRepository::getEntityManager('Supra\Cms\Abstraction\History'),
		);
	}
}
