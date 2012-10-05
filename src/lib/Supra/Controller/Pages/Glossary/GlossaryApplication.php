<?php

namespace Supra\Controller\Pages\Glossary;

use Supra\Controller\Pages\Application\PageApplicationInterface;
use Supra\Controller\Pages\Entity;
use DateTime;
use Supra\Uri\Path;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\NestedSet\SearchCondition\SearchConditionInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Supra\NestedSet\DoctrineRepository;

/**
 * Glossary application
 */
class GlossaryApplication implements PageApplicationInterface
{

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var Entity\ApplicationLocalization
	 */
	protected $applicationLocalization;

	/**
	 * {@inheritdoc}
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}

	/**
	 * {@inheritdoc}
	 * @param Entity\ApplicationLocalization $localization
	 */
	public function setApplicationLocalization(Entity\ApplicationLocalization $applicationLocalization)
	{
		$this->applicationLocalization = $applicationLocalization;
	}

	/**
	 * {@inheritdoc}
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization)
	{
		$path = new Path('');

		return $path;
	}

	/**
	 * {@inheritdoc}
	 * @param QueryBuilder $queryBuilder
	 * @param string $filterName
	 * @return array
	 */
	public function getFilterFolders(QueryBuilder $queryBuilder, $filterName)
	{
		$folders = null;

		if ($filterName == 'list') {
			$folders = array();
//		} else if ($filterName == 'Other') {
//			$folders = array();
		} else if ($filterName == '') {
			$folders = $this->getDefaultFilterFolders();
		} else {
			throw new \RuntimeException("Filter $filterName is not recognized");
		}

		return $folders;
	}

	/**
	 * @return array
	 */
	protected function getlistFilterFolders()
	{
		return array();
	}

	/**
	 * @return array
	 */
	protected function getDefaultFilterFolders()
	{
		$listGroup = new Entity\TemporaryGroupPage();
		$listGroup->setTitle('list');
		$listGroup->setId($this->applicationLocalization->getId() . '_' . 'list');

		//$otherGroup = new Entity\TemporaryGroupPage();
		//$otherGroup->setTitle('Other');
		//$otherGroup->setId($this->applicationLocalization->getId() . '_' . 'Other');

		//return array($listGroup, $otherGroup);
		return array($listGroup);
	}

	/**
	 * {@inheritdoc}
	 * @param QueryBuilder $queryBuilder
	 * @param string $filterName
	 */
	public function applyFilters(QueryBuilder $queryBuilder, $filterName)
	{
		$filterName = (string) $filterName;
		
		if ($filterName == 'list') {
			$this->applyListFilter($queryBuilder);
//		}
//		else if ($filterName == 'Other') {
//
//			$this->applyDefaultFilter($queryBuilder);
		} else if ($filterName == '') {

			$this->applyDefaultFilter($queryBuilder);
		} else {
			throw new \RuntimeException("Filter $filterName is not recognized");
		}
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $queryBuilder
	 */
	protected function applyDefaultFilter(QueryBuilder $queryBuilder)
	{
		$queryBuilder->andWhere('e INSTANCE OF ' . Entity\GroupPage::CN());
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $queryBuilder
	 */
	protected function applyListFilter(QueryBuilder $queryBuilder)
	{
		$queryBuilder
				->addSelect('COALESCE(l_.creationTime, l.creationTime) as HIDDEN ct')
				->andWhere('l INSTANCE OF ' . Entity\PageLocalization::CN())
				->orderBy('ct', 'DESC');
	}

}
