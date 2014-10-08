<?php

namespace Supra\Package\Cms\Configuration;

use Supra\Core\Configuration\AbstractPackageConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SupraPackageCmsConfiguration extends AbstractPackageConfiguration implements ConfigurationInterface
{
	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();

		$treeBuilder->root('cms')
			->children()
				->scalarNode('prefix')->isRequired()->end()
				->scalarNode('active_theme')->end()
				->arrayNode('cms_resources')
					->children()
						->arrayNode('css_pack')->prototype('scalar')->end()->end()
						->arrayNode('js_pack')->prototype('scalar')->end()->end()
					->end()
				->end()
				->append($this->getServicesDefinition())
			->end();

		return $treeBuilder;
	}

}
