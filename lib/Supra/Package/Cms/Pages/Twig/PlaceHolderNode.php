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

class PlaceHolderNode extends \Twig_Node_Expression_Function
{
	public function compile(\Twig_Compiler $compiler)
	{
		$arguments = $this->getNode('arguments');

        if (($count = $arguments->count()) === 1) {

			$arguments = iterator_to_array($arguments->getIterator());

			if ($arguments[0] instanceof \Twig_Node_Expression_Constant) {

				$name = $arguments[0]->getAttribute('value');

				$compiler->raw(sprintf('(isset($context[\'responses\'][\'%1$s\']) ? $context[\'responses\'][\'%1$s\'] : "")', $name));
			}
		}
	}
}