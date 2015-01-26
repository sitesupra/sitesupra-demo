<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Core\NestedSet\SearchCondition;

use Supra\Core\NestedSet\Exception;

/**
 * @method SearchConditionAbstraction leftEqualsTo(int $value)
 * @method SearchConditionAbstraction leftLessThan(int $value)
 * @method SearchConditionAbstraction leftGreaterThan(int $value)
 * @method SearchConditionAbstraction leftLessThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction leftGreaterThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction leftNotEqualsTo(int $value)
 * @method SearchConditionAbstraction rightEqualsTo(int $value)
 * @method SearchConditionAbstraction rightLessThan(int $value)
 * @method SearchConditionAbstraction rightGreaterThan(int $value)
 * @method SearchConditionAbstraction rightLessThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction rightGreaterThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction rightNotEqualsTo(int $value)
 * @method SearchConditionAbstraction levelEqualsTo(int $value)
 * @method SearchConditionAbstraction levelLessThan(int $value)
 * @method SearchConditionAbstraction levelGreaterThan(int $value)
 * @method SearchConditionAbstraction levelLessThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction levelGreaterThanOrEqualsTo(int $value)
 * @method SearchConditionAbstraction levelNotEqualsTo(int $value)
 */
class SearchConditionAbstraction implements SearchConditionInterface
{
	/**
	 * Possible fields for search conditions
	 * @var array
	 */
	private static $fields = array(
		self::LEFT_FIELD,
		self::RIGHT_FIELD,
		self::LEVEL_FIELD,
	);

	/**
	 * Allowed relations and their method names
	 * @var array
	 */
	private static $relationMethods = array(
		self::RELATION_EQUALS => 'equalsTo',
		self::RELATION_LESS => 'lessThan',
		self::RELATION_GREATER => 'greaterThan',
		self::RELATION_LESS_OR_EQUALS => 'lessThanOrEqualsTo',
		self::RELATION_GREATER_OR_EQUALS => 'greaterThanOrEqualsTo',
		self::RELATION_NOT_EQUALS => 'notEqualsTo',
	);
	
	/**
	 * Deprecated method names
	 * TODO: remove later
	 * @var array
	 */
	private static $deprecatedRelationMethods = array(
		self::RELATION_GREATER => 'moreThan',
		self::RELATION_GREATER_OR_EQUALS => 'moreThanOrEqualsTo',
	);

	/**
	 * Collection of conditions
	 * @var array
	 */
	protected $conditions = array();

	/**
	 * Add a condition to the collection
	 * @param string $field
	 * @param string $relation
	 * @param int $value
	 */
	public function add($field, $relation, $value)
	{
		$this->conditions[] = array(
				self::FIELD_POS => $field,
				self::RELATION_POS => $relation,
				self::VALUE_POS => $value
		);
	}

	/**
	 * Magic for condition adding methods
	 * @param string $method
	 * @param array $arguments
	 * @return SearchConditionAbstraction 
	 */
	public function __call($method, $arguments)
	{
		$fieldFound = false;
		$methodRemainder = $method;
		foreach (self::$fields as $field) {
			if (stripos($method, $field) === 0) {
				$methodRemainder = substr($method, strlen($field));
				$fieldFound = $field;
				break;
			}
		}
		if ($fieldFound === false) {
			throw new Exception\BadMethodCall("Unknown method $method called for search condition object, no field match found");
		}
		
		$relationFound = false;
		foreach (self::$relationMethods as $relationTest => $relationString) {
			if (strcasecmp($relationString, $methodRemainder) === 0) {
				$relationFound = $relationTest;
				break;
			}
		}
		
		// Deprecated name
		if ($relationFound === false) {
			foreach (self::$deprecatedRelationMethods as $relationTest => $relationString) {
				if (strcasecmp($relationString, $methodRemainder) === 0) {
//					ObjectRepository::getLogger($this)
//							->warn("Nested set method $method is deprecated, use greaterThan or greaterThanOrEqualsTo instead.");
					$relationFound = $relationTest;
					break;
				}
			}
		}
		
		if ($relationFound === false) {
			throw new Exception\BadMethodCall("Unknown method $method called for search condition object, no relation match found");
		}

		if ( ! isset($arguments[0])) {
			throw new Exception\InvalidArgument("No value passed to method $method");
		}
		if (  ! is_int($arguments[0])) {
			throw new Exception\InvalidArgument("Not integer value passed to method $method");
		}
		$value = (int) $arguments[0];
		
		$this->add($fieldFound, $relationFound, $value);
		
		return $this;
	}
}