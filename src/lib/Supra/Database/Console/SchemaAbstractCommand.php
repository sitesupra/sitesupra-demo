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
			'Public' => ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC),
			'Draft' => ObjectRepository::getEntityManager(PageController::SCHEMA_CMS),
			'Trash' => ObjectRepository::getEntityManager(PageController::SCHEMA_TRASH),
			'History' => ObjectRepository::getEntityManager(PageController::SCHEMA_HISTORY),
		);
	}
}
