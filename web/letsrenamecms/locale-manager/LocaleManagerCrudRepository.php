<?php

// http://www.youtube.com/watch?v=p0PU5RsMEeU
// http://www.youtube.com/watch?v=681NvqpO2eU
// https://www.youtube.com/watch?v=nSVvpJ8K0-4&feature=autoplay&list=AL94UKMTqg-9A81U-Ss-CPAcMTpOEPdjym&playnext=6

namespace Supra\Cms\LocaleManager;

use Supra\Editable;
use Doctrine\ORM\QueryBuilder;
use Supra\Cms\CrudManager;

class LocaleManagerCrudRepository extends \Doctrine\ORM\EntityRepository implements CrudManager\CrudRepositoryInterface
{

	/**
	 * @return array
	 */
	public function getEditableFields()
	{
		$dummyIdEditable = new Editable\String('ID');

		$titleEditable = new Editable\String('Title');

		$countryEditable = new Editable\String('Country');

		$isActiveEditable = new Editable\Checkbox('Active');

		$isDefaultEditable = new Editable\Checkbox('Default');

		$flagPropertyEditable = new Editable\String('Flag');

		$languagePropertyEditable = new Editable\String('Language');

		return array(
			'dummyId' => $dummyIdEditable,
			'title' => $titleEditable,
			'country' => $countryEditable,
			'active' => $isActiveEditable,
			'default' => $isDefaultEditable,
			'flagProperty' => $flagPropertyEditable,
			'languageProperty' => $languagePropertyEditable
		);
	}

	/**
	 * @return array
	 */
	public function getListFields()
	{
		return $this->getEditableFields();
	}

	/**
	 * @return boolean
	 */
	public function isCreatable()
	{
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDeletable()
	{
		return false;
	}

	/**
	 * @return boolean
	 */
	public function isLocalized()
	{
		return false;
	}

	/**
	 * @return boolean
	 */
	public function isSortable()
	{
		return false;
	}

	/**
	 * @param QueryBuilder $qb
	 * @return QueryBuilder
	 */
	public function setAdditionalQueryParams(\Doctrine\ORM\QueryBuilder $qb)
	{
		return $qb;
	}

	/**
	 * @param QueryBuilder $qb
	 * @param FilteredInput $filter
	 * @return QueryBuilder
	 */
	public function applyFilters(QueryBuilder $qb, \Supra\Validator\FilteredInput $filter)
	{
		return $qb;
	}

	/**
	 * @return array
	 */
	public function getFilters()
	{
		return array();
	}
	
}