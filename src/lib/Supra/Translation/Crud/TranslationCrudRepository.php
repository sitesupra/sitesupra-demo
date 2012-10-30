<?php

namespace Supra\Translation\Crud;

use Supra\Cms\CrudManager\CrudRepositoryWithFilterInterface;
use Supra\Editable;
use Doctrine\ORM\QueryBuilder;
use Supra\Translation\Entity\Translation;

class TranslationCrudRepository extends \Doctrine\ORM\EntityRepository implements CrudRepositoryWithFilterInterface
{

	private $allFields;

	private function getAllFields($names = array())
	{
		if (empty($this->allFields)) {
			$disabledOptions = array('disabled' => true);

			$localeSelect = new Editable\Select('Language');
			$languages = array();
			$localeManager = \Supra\ObjectRepository\ObjectRepository::getLocaleManager($this);

			foreach ($localeManager->getLocales() as $locale) {
				/* @var $locale \Supra\Locale\LocaleInterface */
				$languageId = $locale->getId();
				$languages[$languageId] = $locale->getTitle();
			}

			$localeSelect->setValues($languages);

			$domainSelect = new Editable\Select('Domain');
			$domainSelect->setValues(array(
				'messages' => 'Common',
				'validators' => 'Error',
			));

			$statusSelect = new Editable\Select('Source');
			$statusSelect->setValues(array(
				Translation::STATUS_FOUND => Translation::STATUS_FOUND . ' (found in templates)',
				Translation::STATUS_MANUAL => Translation::STATUS_MANUAL . ' (entered manually)',
				Translation::STATUS_IMPORT => Translation::STATUS_IMPORT .' (imported, not changed)',
				Translation::STATUS_CHANGED => Translation::STATUS_CHANGED . ' (imported, changed)',
			));

			$this->allFields = array(
				'search' => new Editable\String('Name and value'),
				'resource' => new Editable\String('Resource', null, $disabledOptions),
				'resourceSearch' => new Editable\String('Resource'),
				'domain' => $domainSelect,
				'locale' => $localeSelect,
				'name' => new Editable\String('Name'),
				'value' => new Editable\Textarea('Translation'),
				'value_short' => new Editable\String('Translation'),
				'status' => new Editable\String('Source'),
				'statusSearch' => $statusSelect,
				'comment' => new Editable\Textarea('Comment'),
			);
		}

		$return = array();

		foreach ($names as $name) {
			$return[$name] = $this->allFields[$name];
		}

		return $return;
	}

	public function getEditableFields()
	{
		return $this->getAllFields(array(
			'resource',
			'domain',
			'locale',
			'name',
			'value',
			'comment',
		));
	}

	/**
	 * @return array
	 */
	public function getListFields()
	{
		return $this->getAllFields(array(
			'resource',
			'domain',
			'locale',
			'name',
			'value_short',
			'status',
		));
	}

	public function isCreatable()
	{
		return true;
	}

	public function isDeletable()
	{
		return true;
	}

	public function isLocalized()
	{
		return false;
	}

	public function isSortable()
	{
		return false;
	}
	
	public function setAdditionalQueryParams(QueryBuilder $qb)
	{
		$qb->orderBy('e.id', 'DESC');
		return $qb;
	}

	public function applyFilters(QueryBuilder $qb, \Supra\Validator\FilteredInput $filter)
	{
		if ( ! $filter->isEmpty('search')) {
			$search = $filter->get('search');
			$qb->andWhere('(e.name LIKE :search OR e.value LIKE :search OR e.comment LIKE :search)')
					->setParameter('search', "%$search%");
		}

		if ( ! $filter->isEmpty('resourceSearch')) {
			$qb->andWhere('e.resource LIKE :resource')
					->setParameter('resource', '%' . $filter->get('resourceSearch') . '%');
		}
		if ( ! $filter->isEmpty('domain')) {
			$qb->andWhere('e.domain = :domain')
					->setParameter('domain', $filter->get('domain'));
		}
		if ( ! $filter->isEmpty('locale')) {
			$qb->andWhere('e.locale = :locale')
					->setParameter('locale', $filter->get('locale'));
		}
		if ( ! $filter->isEmpty('statusSearch')) {
			$qb->andWhere('e.status = :status')
					->setParameter('status', $filter->get('statusSearch'));
		}
	}

	public function getFilters()
	{
		return $this->getAllFields(array(
			'search',
			'resourceSearch',
			'domain',
			'locale',
			'statusSearch',
		));
	}
}
