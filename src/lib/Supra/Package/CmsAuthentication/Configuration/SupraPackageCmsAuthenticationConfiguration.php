<?php

namespace Supra\Package\CmsAuthentication\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SupraPackageCmsAuthenticationConfiguration implements ConfigurationInterface
{
	/**
	 * Generates the configuration tree builder.
	 *
	 * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();

		$treeBuilder->root('cms_authentication')
			->children()
				->scalarNode('login_path')->isRequired()->end()
				->scalarNode('logout_path')->isRequired()->end()
				->scalarNode('login_check_path')->isRequired()->end()
				->arrayNode('anonymous_paths')
					->prototype('scalar')->end()
				->end()
			->end();

		return $treeBuilder;
	}

}
