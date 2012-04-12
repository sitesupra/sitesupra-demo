<?php

namespace Supra\Controller\Pages\News;

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
 * News page application
 */
class NewsApplication implements PageApplicationInterface
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
		$creationTime = $pageLocalization->getCreationTime();

		$pathString = $creationTime->format('Y/m/d');
		$path = new Path($pathString);
		
		return $path;
	}

	/**
	 * {@inheritdoc}
	 * @param string $filter
	 * @return array
	 */
	public function getFilterFolders($filter)
	{
		switch ((string) $filter) {
			case 'list':
				return array();
			
			case '':
				$group = new Entity\TemporaryGroupPage();
				$group->setTitle('list');

				// FIXME: move to sitemap action maybe
				$id = $this->applicationLocalization->getId()
						. '_' . 'list';
				$group->setId($id);

				return array($group);
				
			default:
				throw new \RuntimeException("Filter $filter is not recognized");
		}
	}
	
	/**
	 * {@inheritdoc}
	 * @param QueryBuilder $queryBuilder
	 * @param string $filter
	 */
	public function applyFilters(QueryBuilder $queryBuilder, $filter)
	{
		switch ((string) $filter) {
			case 'list':
				$queryBuilder
					->addSelect('COALESCE(l_.creationTime, l.creationTime) as HIDDEN ct')
					->andWhere('l INSTANCE OF ' . Entity\PageLocalization::CN())
					->orderBy('ct', 'DESC');
				break;
			case '':
				$queryBuilder->andWhere('e INSTANCE OF ' . Entity\GroupPage::CN());
				break;
			default:
				throw new \RuntimeException("Filter $filter is not recognized");
		}
	}
}
