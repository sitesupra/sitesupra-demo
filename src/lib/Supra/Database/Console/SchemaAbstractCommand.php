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
		
		$historyEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\History');
		$listeners = $historyEm->getEventManager()->getListeners(\Doctrine\ORM\Events::loadClassMetadata);
		foreach ($listeners as $listener) {
			if ($listener instanceof \Supra\Controller\Pages\Listener\HistorySchemeModifier) {
				$listeners = $historyEm->getEventManager()->removeEventListener(\Doctrine\ORM\Events::loadClassMetadata, $listener);
			}
		}
		$listener = new \Supra\Controller\Pages\Listener\HistorySchemeModifier();
		$listener->setAsCreateCall();
		$historyEm->getEventManager()->addEventListener(array(\Doctrine\ORM\Events::loadClassMetadata), $listener);

		$this->entityManagers = array(
			'public' => ObjectRepository::getEntityManager(''),
			'draft' => ObjectRepository::getEntityManager('Supra\Cms'),
			'trash' => ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash'),
			'history' => $historyEm,
		);
	}
}
