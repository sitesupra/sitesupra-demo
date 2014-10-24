<?php

namespace Supra\Package\Cms\Pages\DeepCopy;

use Doctrine\ORM\EntityManager;
use DeepCopy\Filter\Filter;

class DoctrineCollectionFilter implements Filter
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
        $oldCollection = $reflectionProperty->getValue($object);

		$entityManager = $this->entityManager;

        $newCollection = $oldCollection->map(
            function ($item) use ($objectCopier, $entityManager) {
                $newItem = $objectCopier($item);

				$entityManager->persist($newItem);

				return $newItem;
            }
        );

        $reflectionProperty->setValue($object, $newCollection);
    }
}