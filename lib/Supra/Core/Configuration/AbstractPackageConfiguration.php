<?php

namespace Supra\Core\Configuration;

use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\PrototypedArrayNode;

abstract class AbstractPackageConfiguration
{
	protected function getServicesDefinition()
	{
		$node = new ArrayNodeDefinition('services');

		$node->prototype('array')
				->children()
					->scalarNode('class')->isRequired()->end()
					->variableNode('parameters')->defaultValue(array())->end()
				->end()
			->end();

		return $node;
	}

}
