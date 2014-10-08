<?php

namespace Supra\Package\Cms\Pages\Application;

use Doctrine\ORM\QueryBuilder;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\TemporaryGroupPage;
use Supra\Package\Cms\Entity\GroupPage;
use Supra\Uri\Path;

/**
 * Glossary application
 */
class GlossaryPageApplication extends PageApplication
{
	protected $id = 'glossary';
	protected $title = 'Glossary';
	protected $icon = '/public/cms/content-manager/sitemap/images/apps/glossary.png';

	/**
	 * {@inheritdoc}
	 * @param PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(PageLocalization $pageLocalization)
	{
		return new Path('');
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
		$listGroup = new TemporaryGroupPage();
		$listGroup->setTitle('list');
		$listGroup->setId($this->applicationLocalization->getId() . '_' . 'list');

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
		$queryBuilder->andWhere('e INSTANCE OF ' . GroupPage::CN());
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $queryBuilder
	 */
	protected function applyListFilter(QueryBuilder $queryBuilder)
	{
		$queryBuilder
				->addSelect('COALESCE(l_.creationTime, l.creationTime) as HIDDEN ct')
				->andWhere('l INSTANCE OF ' . PageLocalization::CN())
				->orderBy('ct', 'DESC');
	}

}
