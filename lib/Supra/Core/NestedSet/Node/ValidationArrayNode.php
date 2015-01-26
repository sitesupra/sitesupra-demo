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

class ValidationArrayNode extends ArrayNode
{
	public $originalData;
	
	public function __construct(NodeInterface $entity)
	{
		$this->originalData = array(
			'id' => $entity->getId(),
			'left' => $entity->getLeftValue(),
			'right' => $entity->getRightValue(),
			'level' => $entity->getLevel(),
			'isLeaf' => ($entity instanceof NodeLeafInterface),
		);
	}

	public function getNodeTitle()
	{
		$id = $this->originalData['id'];

		$leftStatus = $rightStatus = $levelStatus = null;

		if ($this->originalData['left'] != $this->left) {
			$leftStatus = sprintf('LEFT %4d --> %4s', $this->originalData['left'], $this->left);
		}
		if ($this->originalData['right'] != $this->right) {
			$rightStatus = sprintf('RIGHT %4d --> %4s', $this->originalData['right'], $this->right);
		}
		if ($this->originalData['level'] != $this->level) {
			$levelStatus = sprintf('LEVEL %4d --> %4s', $this->originalData['level'], $this->level);
		}
		
		return sprintf('%20s   %20s   %20s   %20s   %20s',
				$id,
				$leftStatus,
				$rightStatus,
				$levelStatus,
				(! $this->isLeaf() && $this->originalData['isLeaf'] ? 'cannot have children' : '')
		);
	}

	public function isOk()
	{
		return $leftStatus = $this->originalData['left'] == $this->left &&
				$rightStatus = $this->originalData['right'] == $this->right &&
				$levelStatus = $this->originalData['level'] == $this->level
				&& ( $this->isLeaf() || ! $this->originalData['isLeaf'] )
				;
	}

	public function getId()
	{
		return $this->originalData['id'];
	}
	
	/**
	 * @return bool
	 */
	public function isOriginallyWithLeafInterface()
	{
		return ($this->originalData['isLeaf'] === true);
	}
}
