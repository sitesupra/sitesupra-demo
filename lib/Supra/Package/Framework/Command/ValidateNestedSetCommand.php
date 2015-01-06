<?php

namespace Supra\Package\Framework\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Supra\Core\NestedSet\Event\NestedSetEvents;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Core\Console\AbstractCommand;
use Supra\Core\NestedSet\ArrayRepository;
use Supra\Core\NestedSet\DoctrineRepository;
use Supra\Core\NestedSet\Node\EntityNodeInterface;
use Supra\Core\NestedSet\RepositoryInterface;
use Supra\Core\NestedSet\Node\ValidationArrayNode;

class ValidateNestedSetCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('supra:nested_set:check')
				->setDescription('Checks nested set indices')
				->addArgument('entity', InputArgument::OPTIONAL, 'Nested set entity name');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$entityNames = $input->getArgument('entity');

		$em = $this->container->getDoctrine()->getManager();
		/* @var $em \Doctrine\ORM\EntityManager */

		if (empty($entityNames)) {
			foreach ($em->getMetadataFactory()->getAllMetadata() as $metaData) {
				/* @var $metaData ClassMetadata */
				if ($metaData->getReflectionClass()
						->implementsInterface('Supra\Core\NestedSet\Node\EntityNodeInterface')
						&& $em->getRepository($metaData->name) instanceof RepositoryInterface) {

					$entityNames[] = $metaData->name;
				}
			}
		}

		if (! is_array($entityNames)) {
			$entityNames = array($entityNames);
		}

		$eventManager = $em->getEventManager();

		// Disable any listener that could rely on nested set.
		foreach (array(Events::onFlush, Events::postFlush, NestedSetEvents::nestedSetPostMove) as $event) {

			if ($eventManager->hasListeners($event)) {
				foreach ($eventManager->getListeners($event) as $listeners) {
					foreach ($listeners as $listener) {
						$eventManager->removeEventListener($event, $listener);
					}
				}
			}
		}

		foreach ($entityNames as $entityName) {
			
			$em->beginTransaction();
			
			$output->writeln("<info>Validating \"{$entityName}\"</info>");
			
			$fixRequired = $this->isFixRequired($em, $output, $entityName);

			if ($fixRequired) {

				// Check again on the fixed data, will give up if set is still broken
				if ($this->isFixRequired($em, $output, $entityName)) {
					$output->writeln("<error>Nested set is broken and cannot be fixed automatically.</error>");
					$em->rollback();

					return;
				}

				$helper = $this->getHelper('question');
				/* @var $helper QuestionHelper */

				if ($helper->ask($input, $output, new ConfirmationQuestion('Do you want to fix the nested set automatically? [y/N] ', false))) {
					$em->commit();
				} else {
					$em->rollBack();
				}
			} else {
				$em->rollback();
			}
			
			$output->writeln('Done.');
		}
	}

	/**
	 * Checks if the nested set fix is required
	 * @param EntityManager $em
	 * @param OutputInterface $output
	 * @param string $entityName
	 * @return boolean
	 */
	protected function isFixRequired(EntityManager $em, $output, $entityName)
	{
		$repository = $em->getRepository($entityName);

		if (! $repository instanceof RepositoryInterface) {
			$output->writeln('<error>The entity name isn\'t configured to be in nested set');
			return false;
		}

		$nestedRepository = $repository->getNestedSetRepository();
		$filter = $nestedRepository->createSearchCondition();
		$order = $nestedRepository->createSelectOrderRule();

		// Order by left index
		$order->byLeftAscending();

		/* @var $nestedRepository DoctrineRepository */

		$qb = $nestedRepository->createSearchQueryBuilder($filter, $order);

		$qb->select('e');
		
		$query = $qb->getQuery();
			
		$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
		$query->setHydrationMode(Query::HYDRATE_SIMPLEOBJECT);

		$records = $query->getResult();

		// Keeps pile of current parents
		$parents = array();

		$arrayRepository = new ArrayRepository();

		$nodes = array();
		/* @var $nodes ValidationArrayNode[] */

		foreach ($records as $record) {

			if (! $record instanceof EntityNodeInterface) {
				throw new \UnexpectedValueException(sprintf(
					'Expecting DoctrineNode, [%s] received.',
					get_class($record)
				));
			}

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
			if (! $node->isOk()) {

				// Output index changes suggested
				$output->writeln("Node " . $node->getNodeTitle());

				$dbEntity = $em->find($entityName, $node->getId());
				/* @var $dbEntity EntityNodeInterface */

				if ( ! $node->isLeaf() && $node->isOriginallyWithLeafInterface() ) {

					$children = $node->getChildren();
					foreach ($children as $child) {
						
						if (! $node->isOk()) {
							$child->moveAsNextSiblingOf($node);
						} else {
							$child->moveAsPrevSiblingOf($node);
						}
					}
					
				} else {
					// Overwrite the indices
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
}
