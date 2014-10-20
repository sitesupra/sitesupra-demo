<?php

// @FIXME

namespace Supra\NestedSet\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Supra\ObjectRepository\ObjectRepository;
use Supra\NestedSet\RepositoryInterface;
use Supra\FileStorage\Listener\FilePathGenerator;
use Supra\Controller\Pages\Listener\PagePathGenerator;

/**
 * Validates nested set for the provided entity name and provides autofixing
 */
class ValidateNestedSetCommand extends Command
{
	/**
	 * Configure
	 */
	protected function configure()
	{
		$this->setName('su:nested_set:check')
				->setDescription('Cheks nested set indecies')
				->addArgument('entity', InputArgument::OPTIONAL, 'Nested set entity name');
	}

	/**
	 * Execute command
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$entityNames = $input->getArgument('entity');
		
		if (empty($entityNames)) {
			$entityNames = array(
				\Supra\Controller\Pages\Entity\Page::CN(),
				\Supra\Controller\Pages\Entity\Template::CN(),
				\Supra\FileStorage\Entity\Abstraction\File::CN(),
			);
		}
		
		if ( ! is_array($entityNames)) {
			$entityNames = array($entityNames);
		}

		$em = ObjectRepository::getEntityManager($this);
		$this->removeListeners($em);
		
		foreach ($entityNames as $entityName) {
			
			$em->beginTransaction();
			
			$output->writeln("<info>Validating \"{$entityName}\"</info>");
			
			$fixRequired = $this->isFixRequired($em, $output, $entityName);

			if ($fixRequired) {

				// Check again on the fixed data, will give up if set is still broken
				$fixStillRequired = $this->isFixRequired($em, $output, $entityName);

				if ($fixStillRequired) {
					$output->writeln("<error>Could not repair.</error>");
					$em->rollback();
					return;
				}

				// Suggest autofix
				$commit = $this->prompt($output, '<question>Do you want to fix the nested set automatically? [y/N]</question> ');

				if ($commit) {
					$em->commit();
				} else {
					$em->rollback();
				}
			} else {
				$em->rollback();
			}
			
			$output->writeln('Done check.');
		}
	}

	/**
	 * Stops the known listeners which relies on correct nested set
	 * @param \Doctrine\ORM\EntityManager $em
	 */
	protected function removeListeners(\Doctrine\ORM\EntityManager $em)
	{
		$eventManager = $em->getEventManager();
		$listeners = $eventManager->getListeners();

		foreach ($listeners as $eventName => $listenerList) {
			foreach ($listenerList as $listener) {
				if ($listener instanceof FilePathGenerator || $listener instanceof PagePathGenerator) {
					$eventManager->removeEventListener($eventName, $listener);
				}
			}
		}
	}

	/**
	 * Checks if the nested set fix is required
	 * @param \Doctrine\ORM\EntityManager $em
	 * @param OutputInterface $output
	 * @param string $entityName
	 * @return boolean
	 */
	protected function isFixRequired($em, $output, $entityName)
	{
		$repository = $em->getRepository($entityName);

		if ( ! $repository instanceof RepositoryInterface) {
			$output->writeln('<error>The entity name isn\'t configured to be in nested set');
			return;
		}

		$nestedRepository = $repository->getNestedSetRepository();
		$filter = $nestedRepository->createSearchCondition();
		$order = $nestedRepository->createSelectOrderRule();

		// Order by left index
		$order->byLeftAscending();

		/* @var $nestedRepository \Supra\NestedSet\DoctrineRepository */

		$qb = $nestedRepository->createSearchQueryBuilder($filter, $order);

		$qb->select('e');
		
		$query = $qb->getQuery();
			
		$query->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
		$query->setHydrationMode(\Doctrine\ORM\Query::HYDRATE_SIMPLEOBJECT);

		$records = $query->getResult();
		
		/**
		 * Keeps pile of current parents
		 * @var $parents array
		 */
		$parents = array();

		/**
		 * Uses simple array nested set repository to recalculate the indecies
		 * @var $arrayRepository \Supra\NestedSet\ArrayRepository
		 */
		$arrayRepository = new \Supra\NestedSet\ArrayRepository();
		$nodes = array();

		foreach ($records as $record) {
			$level = $record->getLevel();

			$node = new ValidationArrayNode($record);
			$nodes[] = $node;
			$arrayRepository->add($node);

			// Fixing strongly relies on the level
			if ($level > 0) {
				$parent = null;

				if (isset($parents[$level - 1])) {
					$parent = $parents[$level - 1];
				}  else {
					// Too fast level drop, takes the deepest parent
					$parent = end($parents);
				}

				if ( ! empty($parent)) {
					$parent->addChild($node);
				}

			}

			$newLevel = $node->getLevel();

			// Removing historical parents deeper than the current node
			$parents = array_slice($parents, 0, $newLevel);
			$parents[$newLevel] = $node;
		}

		$fixRequired = false;

		foreach ($nodes as $node) {
			
			if ( ! $node->isOk()) {

				// Output index changes suggested
				$output->writeln("Node " . $node->getNodeTitle());

				$dbEntity = $em->find($entityName, $node->getId());
				/* @var $dbEntity \Supra\NestedSet\Node\EntityNodeInterface */

				if ( ! $node->isLeaf() && $node->isOriginallyWithLeafInterface() ) {

					$children = $node->getChildren();
					foreach ($children as $child) {
						
						if ( ! $node->isOk()) {
							$child->moveAsNextSiblingOf($node);
						} else {
							$child->moveAsPrevSiblingOf($node);
						}
					}
					
				} else {
				// Overwrite the indecies
					$dbEntity->setLeftValue($node->getLeftValue());
					$dbEntity->setRightValue($node->getRightValue());

					$dbEntity->setLevel($node->getLevel());
				}

				$fixRequired = true;
			}
		}

		// Flushing, commit or rollback done later
		if ($fixRequired) {
			$em->flush();
		}

		return $fixRequired;
	}

	/**
	 * Asks Y/N question
	 * @param OutputInterface $output
	 * @param string $message
	 * @return boolean
	 */
	protected function prompt($output, $message)
	{
		$dialog = $this->getHelper('dialog');

		$answer = null;

		while ( ! in_array($answer, array('Y', 'N', ''), true)) {
			$answer = strtoupper($dialog->ask($output, $message));
		}

		if ($answer === 'Y') {
			return true;
		}

		return false;
	}
}
