<?php

namespace Supra\Database\Console;

use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\PageController;

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
			'Public' => ObjectRepository::getEntityManager('#public'),
			'Draft' => ObjectRepository::getEntityManager('#cms'),
			//'trash' => ObjectRepository::getEntityManager('#trash'),
			//'history' => ObjectRepository::getEntityManager('#history'),
			// EXPERIMENTAL
			'Audit' => ObjectRepository::getEntityManager('#audit'),
		);
	}
}
