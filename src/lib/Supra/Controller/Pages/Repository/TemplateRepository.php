<?php

namespace Supra\Controller\Pages\Repository;

use Supra\Controller\Pages\Entity;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;

/**
 * Template repository
 */
class TemplateRepository extends PageAbstractRepository
{
	/**
	 * @param EntityManager $em
	 * @param ClassMetadata $class
	 */
	public function __construct(EntityManager $em, ClassMetadata $class)
	{
		parent::__construct($em, $class);

		// Bind additional conditions to the nested set repository
		$entityName = Entity\Template::CN();

		$additionalCondition = "e INSTANCE OF $entityName";;
		$additionalConditionSql = "discr IN ('template')";

		$this->nestedSetRepository->setAdditionalCondition($additionalCondition, $additionalConditionSql);
	}
}
