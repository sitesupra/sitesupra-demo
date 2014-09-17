<?php

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
