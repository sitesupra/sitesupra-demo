<?php

namespace Supra\Package\Framework\Configuration;

use Supra\Core\Configuration\AbstractPackageConfiguration;
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
					->arrayNode('locale_detectors')
						->isRequired()
						->requiresAtLeastOneElement()
						->prototype('scalar')->end()
					->end()
					->arrayNode('locale_storage')
						->isRequired()
						->requiresAtLeastOneElement()
						->prototype('scalar')->end()
					->end()
					->scalarNode('current_locale')->isRequired()->end()
					->append($this->getServicesDefinition())
				->end();

		return $treeBuilder;
	}

}
