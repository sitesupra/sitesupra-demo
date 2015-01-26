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

use \Twig_NodeInterface;
use \Twig_Environment;

class PlaceHolderNodeCollector implements \Twig_NodeVisitorInterface
{
	/**
	 * @var array
	 */
	private $names = array();

	public function enterNode(Twig_NodeInterface $node, Twig_Environment $env)
	{
		if ($node instanceof \Twig_Node_Module) {

			$parent = $node->getNode('parent');

			if ($parent instanceof \Twig_Node_Expression_Constant) {
				$templateName = $parent->getAttribute('value');

				$collector = new PlaceHolderNodeCollector();

				$traverser = new \Twig_NodeTraverser($env, array($collector));

				$stream = $env->tokenize($env->getLoader()->getSource($templateName));

				$traverser->traverse($env->getParser()->parse($stream));

				$this->names = array_merge($this->names, $collector->getCollectedNames());
			}

		} elseif ($node instanceof PlaceHolderNode) {

			$arguments = $node->getNode('arguments');
			
			if ($arguments->count() !== 1) {
				throw new \UnexpectedValueException('Expecting only name definition inside place holder function.');
			}

			$nameNode = $arguments->getIterator()->current();

			if (! $nameNode instanceof \Twig_Node_Expression_Constant) {
				throw new \UnexpectedValueException('Name definition should be constant expression only.');
			}

			$this->names[] = $nameNode->getAttribute('value');
		}

		return $node;
	}

	/**
	 * {@inheritDoc}
	 */
	public function leaveNode(Twig_NodeInterface $node, Twig_Environment $env)
	{
		return $node;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function getPriority()
	{
		return 0;
	}

	/**
	 * @return array
	 */
	public function getCollectedNames()
	{
		return $this->names;
	}
}
