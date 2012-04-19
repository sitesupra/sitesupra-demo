<?php

namespace Supra\Controller\Pages\Repository;

use Supra\Controller\Pages\Entity;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

/**
 * Page repository, used for pages, applications, groups, NOT templates
 */
class PageRepository extends PageAbstractRepository
{
	/**
	 * @param EntityManager $em
	 * @param ClassMetadata $class
	 */
	public function __construct(EntityManager $em, ClassMetadata $class)
	{
		parent::__construct($em, $class);
		
		// Bind additional conditions to the nested set repository
		$entities = array(
			Entity\Page::CN(),
			Entity\ApplicationPage::CN(),
			Entity\GroupPage::CN()
		);
		
		$orList = array();
		
		foreach ($entities as $entityName) {
			$orList[] = "e INSTANCE OF $entityName";
		}
		
		$additionalCondition = implode(' OR ', $orList);
		$this->nestedSetRepository->setAdditionalCondition($additionalCondition);
	}
}