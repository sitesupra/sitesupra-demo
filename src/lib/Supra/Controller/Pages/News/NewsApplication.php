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
	 * @param QueryBuilder $queryBuilder
	 * @param string $filter
	 * @return array
	 */
	public function getFilterFolders(QueryBuilder $queryBuilder, $filter)
	{
		switch ((string) $filter) {
			case 'group':

				$queryBuilder = clone($queryBuilder);

				$queryBuilder->select('COALESCE(l_.creationYear, l.creationYear) AS year, COALESCE(l_.creationMonth, l.creationMonth) AS month, COUNT(e.id) AS childrenCount')
						->addSelect('COALESCE(l_.creationTime, l.creationTime) as HIDDEN ct')
						->groupBy('year, month')
						->orderBy('ct', 'DESC');

				$months = $queryBuilder->getQuery()
						->getResult();

				$folders = array();

				foreach ($months as $monthData) {

					$year = $monthData['year'];
					$month = $monthData['month'];
					$numberChildren = $monthData['childrenCount'];

					if ($year <= 0 || $month <= 0) {
						$yearMonth = '0000-00';
						$yearMonthTitle = $yearMonth; //'Unknown';
					} else {
						$yearMonth = str_pad($year, 4, '0', STR_PAD_LEFT) . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
						$yearMonthTitle = $yearMonth;
					}

					$group = new Entity\TemporaryGroupPage();
					$group->setTitle($yearMonthTitle);
					$group->setNumberChildren($numberChildren);

					$id = $this->applicationLocalization->getId()
							. '_' . $yearMonth;
					$group->setId($id);

					$folders[] = $group;

				}
				return $folders;

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
				return array();
		}
	}

	/**
	 * {@inheritdoc}
	 * @param QueryBuilder $queryBuilder
	 * @param string $filter
	 */
	public function applyFilters(QueryBuilder $queryBuilder, $filter)
	{
		$filter = (string) $filter;

		switch ($filter) {
			case 'list':
				$queryBuilder
						->addSelect('COALESCE(l_.creationTime, l.creationTime) as HIDDEN ct')
						->andWhere('l INSTANCE OF ' . Entity\PageLocalization::CN())
						->orderBy('ct', 'DESC');
				break;
			case 'group':
			case '':
				$queryBuilder->andWhere('e INSTANCE OF ' . Entity\GroupPage::CN());
				break;
			default:

				$matches = array();
				$isMonthFormat = preg_match('/^(\d{4})\-(\d{2})$/', $filter, $matches);
				
				if ($isMonthFormat) {
					$year = (int) $matches[1];
					$month = (int) $matches[2];

					if ($year > 0 && $month > 0) {
						$queryBuilder->andWhere('COALESCE(l_.creationYear, l.creationYear) = :year')
								->setParameter('year', $year);

						$queryBuilder->andWhere('COALESCE(l_.creationMonth, l.creationMonth) = :month')
								->setParameter('month', $month);
					} else {
						$queryBuilder->andWhere('COALESCE(l_.creationYear, l.creationYear) <= 0 OR COALESCE(l_.creationMonth, l.creationMonth) <= 0');
					}

					break;
				} else {
					throw new \RuntimeException("Filter $filter is not recognized");
				}
		}
	}
}
