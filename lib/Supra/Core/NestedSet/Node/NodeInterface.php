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

namespace Supra\Core\NestedSet\Node;

/**
 * Node interface for nested set nodes and Doctrine entities
 */
interface NodeInterface
{
	/**
	 * Get node's left value of interval
	 * @return int
	 */
	public function getLeftValue();

	/**
	 * Get node's right value of interval
	 * @return int
	 */
	public function getRightValue();

	/**
	 * Get node's depth level
	 * @return int
	 */
	public function getLevel();

	/**
	 * Set node's left value of interval
	 * @param int $left
	 * @return NodeInterface
	 */
	public function setLeftValue($left);

	/**
	 * Set node's right value of interval
	 * @param int $right
	 * @return NodeInterface
	 */
	public function setRightValue($right);

	/**
	 * Set node's depth level
	 * @param int $level
	 * @return NodeInterface
	 */
	public function setLevel($level);

	/**
	 * Increase left value
	 * @param int $diff
	 * @return NodeInterface
	 */
	public function moveLeftValue($diff);

	/**
	 * Increase right value
	 * @param int $diff
	 * @return NodeInterface
	 */
	public function moveRightValue($diff);

	/**
	 * Increase level value
	 * @param int $diff
	 * @return NodeInterface
	 */
	public function moveLevel($diff);
	
	/**
	 * Nested node title
	 * @return string
	 */
	public function getNodeTitle();
}