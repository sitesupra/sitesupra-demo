<?php

namespace Supra\Package\Framework\Configuration;

use Supra\Core\Configuration\AbstractPackageConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SupraPackageFrameworkConfiguration extends AbstractPackageConfiguration implements ConfigurationInterface
{
	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();

		$treeBuilder->root('framework')
				->children()
					->append($this->getLocalesDefinition())
					->append($this->getServicesDefinition())
				->end();

		return $treeBuilder;
	}

	public function getLocalesDefinition()
	{
		$definition = new ArrayNodeDefinition('locales');

		$definition->children()
			->arrayNode('locales')
				->prototype('array')
					->children()
						->scalarNode('id')->isRequired()->end()
						->scalarNode('title')->isRequired()->end()
						->scalarNode('country')->isRequired()->end()
						->arrayNode('properties')
							->children()
								->scalarNode('language')->isRequired()->end()
								->scalarNode('flag')->isRequired()->end()
							->end()
						->end()
						->booleanNode('active')->defaultValue(true)->end()
					->end()
				->end()
			->end()
			->arrayNode('detectors')
				->isRequired()
				->requiresAtLeastOneElement()
				->prototype('scalar')->end()
			->end()
			->arrayNode('storage')
				->isRequired()
					// @FIXME: locale storage is isn't working.
					// ->requiresAtLeastOneElement()
				->prototype('scalar')->end()
			->end()
			->scalarNode('current')->isRequired()->end()
		;

		return $definition;
	}

}
