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

namespace Supra\Core\Routing\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class RoutingConfiguration implements ConfigurationInterface
{
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();

		$treeBuilder->root('routing')
				->children()
					->arrayNode('configuration')->isRequired()
						->children()
							->scalarNode('prefix')->isRequired()->end()
							->arrayNode('defaults')
								->prototype('scalar')->end()
							->end()
						->end()
					->end()
					->arrayNode('routes')
						->prototype('array')
							->children()
								->scalarNode('pattern')->isRequired()->end()
								->scalarNode('controller')->isRequired()->end()
								->arrayNode('filters')
									->prototype('scalar')->end()
								->end()
								->arrayNode('requirements')
									->prototype('scalar')->end()
								->end()
								->arrayNode('defaults')
									->prototype('scalar')->end()
								->end()
								->arrayNode('options')
									->prototype('scalar')->end()
							->end()
						->end()
					->end()
				->end()
				;

		return $this->treeBuilder = $treeBuilder;
	}
}
