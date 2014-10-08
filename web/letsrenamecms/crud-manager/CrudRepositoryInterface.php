<?php

namespace Supra\Cms\CrudManager;

use Doctrine\ORM\QueryBuilder;

interface CrudRepositoryInterface
{

	/**
	 * @example array(
	 *		'working_time' => new Editable\String('Working time'),
	 *		'phone' => new Editable\String('Phone'),
	 *		'fax' => new Editable\String('Fax'),
	 *		'email' => new Editable\String('Email'),
	 *		'location' => new Editable\Map('Location'),
	 *	);
	 * 
	 * @return array
	 */
	public function getEditableFields();

	/**
	 * @example same as getEditableFields();
	 * @return array
	 */
	public function getListFields();
	
	/**
	 * Function for setting ordering and additional parameters
	 * before getting result to output in CRUD manager
	 * 
	 * QueryBuilder CRUD entity alias is "e"
	 *
	 * @example $qb->addOrdering('e.title', 'asc');
	 * @param QueryBuilder $qb
	 * @return QueryBuilder
	 */
	public function setAdditionalQueryParams(QueryBuilder $qb);

	/**
	 * @return boolean
	 */
	public function isSortable();

	/**
	 * @return boolean
	 */
	public function isDeletable();

	/**
	 * @return boolean
	 */
	public function isLocalized();

	/**
	 * @return boolean
	 */
	public function isCreatable();
}