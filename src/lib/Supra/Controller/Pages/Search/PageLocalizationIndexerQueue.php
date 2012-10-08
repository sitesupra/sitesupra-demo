<?php

namespace Supra\Controller\Pages\Search;

use Supra\Search\IndexerQueue;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Search\IndexerQueueItemStatus;
use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Entity\PageLocalizationIndexerQueueItem;
use Doctrine\ORM\QueryBuilder;

class PageLocalizationIndexerQueue extends IndexerQueue
{

	/**
	 * @var string
	 */
	protected $schemaName;

	/**
	 * @param string $schemaName 
	 */
	function __construct($schemaName)
	{
		$this->schemaName = $schemaName;
		parent::__construct(PageLocalizationIndexerQueueItem::CN());
	}

	/**
	 * @param PageLocalization $pageLocalization
	 * @param integer $status
	 * @return IndexerQueueItem
	 */
	public function getOneByObjectAndStatus($pageLocalization, $status)
	{
		$criteria = array(
			'pageLocalizationId' => $pageLocalization->getId(),
			'schemaName' => $this->schemaName,
			'revisionId' => $pageLocalization->getRevisionId(),
			'status' => $status
		);

		$queueItem = $this->repository->findOneBy($criteria);

		return $queueItem;
	}

	/**
	 * @param QueryBuilder $dqb 
	 */
	protected function buildStatusQuery($dqb)
	{
		parent::buildStatusQuery($dqb);
		$this->addSchemaConditionToBuilder($dqb);
	}

	/**
	 * @param QueryBuilder $dqb 
	 */
	protected function buildItemCountForStatusQuery($dqb)
	{
		parent::buildItemCountForStatusQuery($dqb);
		$this->addSchemaConditionToBuilder($dqb);
	}

	/**
	 *
	 * @param QueryBuil $dqb 
	 */
	protected function buildNextItemForStatusQuery($dqb)
	{
		parent::buildNextItemForStatusQuery($dqb);
		$this->addSchemaConditionToBuilder($dqb);
	}

	/**
	 * @param QueryBuilder $dqb 
	 */
	protected function buildRemoveAllQuery($dqb)
	{
		parent::buildRemoveAllQuery($dqb);
		$this->addSchemaConditionToBuilder($dqb);
	}

	/**
	 * @param QueryBuilder $dqb 
	 */
	private function addSchemaConditionToBuilder($dqb)
	{
		$dqb->setParameter('schemaName', $this->schemaName);
		$dqb->andWhere($dqb->expr()->eq('iq.schemaName', ':schemaName'));
	}

	/**
	 * @param PageLocalizationIndexerQueueItem $pageLocalizationIndexerQueueItem 
	 */
	public function store(PageLocalizationIndexerQueueItem $pageLocalizationIndexerQueueItem)
	{
		if ($this->schemaName == PageController::SCHEMA_PUBLIC) {

			$criteria = array(
				'pageLocalizationId' => $pageLocalizationIndexerQueueItem->getPageLocalizationId(),
				'status' => IndexerQueueItemStatus::FRESH,
				'schemaName' => $this->schemaName
			);

			$queueItem = $this->repository->findOneBy($criteria);
			/* @var $queueItem PageLocalizationIndexerQueueItem */

			if ( ! empty($queueItem)) {
				
				if ($queueItem->getRevisionId() != $pageLocalizationIndexerQueueItem->getRevisionId()) {
					$this->em->remove($queueItem);
				}
			} else {
				
			}
		}

		$pageLocalizationIndexerQueueItem->setSchemaName($this->schemaName);
		parent::store($pageLocalizationIndexerQueueItem);
	}

	/**
	 * @param object $object
	 * @param int $priority
	 * @return PageLocalizationIndexerQueueItem
	 */
	public function addRemoval($object, $priority = IndexerQueueItem::DEFAULT_PRIORITY)
	{
		$newQueueItem = $this->create($object, $priority);
		/* @var $newQueueItem PageLocalizationIndexerQueueItem */

		$newQueueItem->setRemoval(true);
		$this->store($newQueueItem);

		return $newQueueItem;
	}

}
