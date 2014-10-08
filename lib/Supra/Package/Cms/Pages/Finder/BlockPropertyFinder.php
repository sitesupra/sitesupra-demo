<?php

namespace Supra\Package\Cms\Pages\Finder;

use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Connection;
use Supra\Controller\Pages\Entity\BlockProperty;

/**
 * BlockPropertyFinder
 */
class BlockPropertyFinder extends AbstractFinder
{

	/**
	 * @var LocalizationFinder
	 */
	private $localizationFinder;

	/**
	 * @var array
	 */
	protected $components = array();

	/**
	 * @param PageFinder $pageFinder
	 */
	public function __construct(LocalizationFinder $localizationFinder)
	{
		$this->localizationFinder = $localizationFinder;

		parent::__construct($localizationFinder->getEntityManager());
	}

	/**
	 * @return QueryBuilder
	 */
	protected function doGetQueryBuilder()
	{
		$qb = $this->localizationFinder->getQueryBuilder();
		$qb = clone($qb);

		$qb->from(BlockProperty::CN(), 'bp');
		$qb->andWhere('bp.localization = l');
		$qb->join('bp.localization', 'l3');
		$qb->join('bp.block', 'b');
		$qb->join('b.placeHolder', 'ph');
		$qb->leftJoin('bp.metadata', 'bpm');
		$qb->leftJoin('bpm.referencedElement', 're');
		$qb->join('l3.master', 'e3');
		$qb->join('l3.path', 'lp3');

		$qb->select('bp, b, l3, e3, bpm, ph, lp3, re');

		$qb = $this->prepareComponents($qb);

		return $qb;
	}

	public function addFilterByComponent($component, $fields = null)
	{
		$this->components[$component] = (array) $fields;
	}

	protected function prepareComponents($qb)
	{
		if ( ! empty($this->components)) {
			$or = $qb->expr()->orX();
			$i = 1;

			foreach ($this->components as $component => $fields) {
				$and = $qb->expr()->andX();
				$and->add("b.componentClass = :component_$i");
				$qb->setParameter("component_$i", $component);

				if ( ! empty($fields)) {
					$and->add("bp.name IN (:fields_$i)");
					$qb->setParameter("fields_$i", $fields, Connection::PARAM_STR_ARRAY);
				}

				$or->add($and);
				$i ++;
			}

			$qb->andWhere($or);
		}

		return $qb;
	}

}
