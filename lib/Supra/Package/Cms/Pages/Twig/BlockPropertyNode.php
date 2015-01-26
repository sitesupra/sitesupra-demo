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

namespace Supra\Package\Cms\Pages\Twig;

use Twig_Node_Expression_Array as ArrayNode;
use Twig_Node_Expression_Constant as ConstantNode;

class BlockPropertyNode extends AbstractPropertyFunctionNode
{
	/**
	 * {@inheritDoc}
	 */
	public function getType()
	{
		return 'property';
	}

	/**
	 * {@inheritDoc}
	 */
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if (($count = $arguments->count()) > 0) {

			$arguments = iterator_to_array($arguments->getIterator());

			$compiler->raw('$this->env->getExtension(\'supraPage\')->getPropertyValue(\'' . $this->getNameOptionValue() . "'");

			if (! empty($arguments[1])) {
				$compiler->raw(',');
				$compiler->subcompile($arguments[1]);
			}

			$compiler->raw(')');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOptions()
	{
		$node = $this->getOptionsArgumentNode();

		if ($node instanceof ConstantNode) {
			return array('name' => $node);
		} elseif ($node instanceof ArrayNode) {
			return $this->nodeToArray($node);
		}

		throw new \UnexpectedValueException('Expecting only string and array nodes.');
	}

//	/**
//	 * @throws \RuntimeException
//	 */
//	public function validate()
//	{
//		$arguments = $this->getNode('arguments');
//
//		if ($arguments->count() > 2) {
//			throw new \RuntimeException('Property definition contains more arguments that expected.');
//		}
//
//		$propertyOptions = $this->getPropertyOptions();
//
//		if (empty($propertyOptions['name'])) {
//			throw new \RuntimeException('Property name cannot be empty.');
//		}
//
//		$filterOptionsNode = $this->getFilterOptionsNode();
//
//		if ($filterOptionsNode !== null
//			&& ! $filterOptionsNode instanceof ArrayNode) {
//			throw new \RuntimeException('Filter options should be an array.');
//		}
//	}
//
//	/**
//	 * @return \Twig_Node|null
//	 */
//	private function getFilterOptionsNode()
//	{
//		return $this->getNode('arguments')->hasNode(1)
//			? $this->getNode('arguments')->getNode(1) : null;
//	}
}