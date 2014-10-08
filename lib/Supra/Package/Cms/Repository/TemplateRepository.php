<?php

namespace Supra\Package\Cms\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Supra\Package\Cms\Entity\Template;

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
		$this->nestedSetRepository->setAdditionalCondition(
				sprintf('e INSTANCE OF %s', Template::CN()),
				'discr IN ("template")'
		);
	}
}
