<?php

namespace Supra\Package\Cms\Pages\DeepCopy;

use Doctrine\ORM\EntityManager;
use DeepCopy\Filter\Filter;

class DoctrineEntityFilter implements Filter
{
	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @param \Doctrine\ORM\EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
     * {@inheritdoc}
	 */
	public function apply($object, $property, $objectCopier)
	{
		$reflectionProperty = new \ReflectionProperty($object, $property);

        $reflectionProperty->setAccessible(true);
		
        $newValue = $objectCopier($reflectionProperty->getValue($object));

		$this->entityManager->persist($newValue);

        $reflectionProperty->setValue($object, $newValue);
	}
}