<?php

namespace Supra\Controller\Pages\Entity;

use Supra\Search\Entity\Abstraction\IndexerQueueItem;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Search\IndexerService;
use Supra\ObjectRepository\ObjectRepository;

/**
 * @Entity
 */
class PageIndexerQueueItem extends IndexerQueueItem
{

	/**
	 * @Column(type="sha1")
	 * @var string
	 */
	protected $pageId;

	/**
	 * @Column(type="integer") 
	 * @var integer
	 */
	protected $revision;

	public function __construct(PageLocalization $pageLocalization)
	{
		$this->pageId = $pageLocalization->getId();
		$this->revision = $pageLocalization->getRevisionData()->getId();
	}

	public function getData()
	{
		/* @var $lr EntityRepository */
		$lr = ObjectRepository::getEntityManager($this)->getRepository(PageLocalization::CN());
		$pageLocalization = $lr->find(arary($this->pageId, $this->revision));
	}

}
