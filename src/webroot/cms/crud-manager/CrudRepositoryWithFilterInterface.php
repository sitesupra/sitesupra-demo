<?php

namespace Supra\Cms\CrudManager;

use Doctrine\ORM\QueryBuilder;
use Supra\Validator\FilteredInput;

/**
 *
 */
interface CrudRepositoryWithFilterInterface extends CrudRepositoryInterface
{
	/**
	 * @param \Doctrine\ORM\QueryBuilder $qb
	 * @param \Supra\Validator\FilteredInput $filter
	 */
	public function applyFilters(QueryBuilder $qb, FilteredInput $filter);

	/**
	 * @return array
	 */
	public function getFilters();
}
