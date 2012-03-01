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
	protected $entityManagers = array();

	/**
	 * Constructor
	 *
	 * @param string $name
	 */
	public function __construct($name = null)
	{
		parent::__construct($name);

		$entityManagers = ObjectRepository::getAllObjects(ObjectRepository::INTERFACE_ENTITY_MANAGER);
		$index = 0;

		foreach ($entityManagers as $entityManager) {

			//FIXME: Have to use the debug _mode property to show some friendly name...
			$mode = $entityManager->_mode;

			// Shouldn't happen, still should need to update everything
			if (isset($this->entityManagers[$mode]) && $this->entityManagers[$mode] !== $entityManager) {
				$mode .= '$' . ++$index;
			}

			$this->entityManagers[$mode] = $entityManager;
		}
	}
}
