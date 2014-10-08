<?php

namespace Supra\Package\Cms\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Supra\Package\Cms\Entity\Page;
use Supra\Package\Cms\Entity\ApplicationPage;
use Supra\Package\Cms\Entity\GroupPage;

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
			Page::CN(),
			ApplicationPage::CN(),
			GroupPage::CN()
		);
		
		$orList = array();
		
		foreach ($entities as $entityName) {
			$orList[] = "e INSTANCE OF $entityName";
		}
		
		$additionalCondition = implode(' OR ', $orList);
		$additionalConditionSql = "discr IN ('page', 'application', 'group')";

		$this->nestedSetRepository->setAdditionalCondition($additionalCondition, $additionalConditionSql);
	}
}