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

namespace Supra\Package\CmsAuthentication\Configuration;

use Supra\Core\Configuration\AbstractPackageConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SupraPackageCmsAuthenticationConfiguration extends AbstractPackageConfiguration implements ConfigurationInterface
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
				->append($this->getPathConfiguration())
				->append($this->getSessionConfiguration())
				->append($this->getUsersConfiguration())
				->append($this->getServicesDefinition())
			->end();

		return $treeBuilder;
	}

	protected function getUsersConfiguration()
	{
		$definition = new ArrayNodeDefinition('users');

		$definition->children()
				->scalarNode('default_domain')->isRequired()->end()
				->arrayNode('shared_connection')
					->performNoDeepMerging()
					->children()
					->scalarNode('host')->end()
					->scalarNode('user')->end()
					->scalarNode('password')->end()
					->scalarNode('dbname')->end()
					->scalarNode('driver')->end()
					->scalarNode('charset')->end()
					->scalarNode('event_manager')->end()
					->end()
				->end()
				->arrayNode('user_providers')
					->performNoDeepMerging()
					->children()
						->arrayNode('doctrine')
							->prototype('array')
								->children()
									->scalarNode('em')->isRequired()->end()
									->scalarNode('entity')->isRequired()->end()
								->end()
							->end()
						->end()
					->end()
				->end()
				->arrayNode('provider_chain')
					->performNoDeepMerging()
					->requiresAtLeastOneElement()
					->prototype('scalar')
					->end()
				->end()
				->scalarNode('provider_key')->isRequired()->end()
				->arrayNode('password_encoders')
					->requiresAtLeastOneElement()
					->prototype('scalar')->end()
				->end()
				->arrayNode('authentication_providers')
					->requiresAtLeastOneElement()
					->prototype('scalar')->end()
				->end()
				->arrayNode('voters')
					->requiresAtLeastOneElement()
					->prototype('scalar')->end()
				->end()
			->end();

		return $definition;
	}

	protected function getSessionConfiguration()
	{
		$definition = new ArrayNodeDefinition('session');

		$definition->children()
				->scalarNode('storage_key')->isRequired()->end()
			->end();

		return $definition;
	}

	protected function getPathConfiguration()
	{
		$definition = new ArrayNodeDefinition('paths');

		$definition->children()
				->scalarNode('login')->isRequired()->end()
				->scalarNode('logout')->isRequired()->end()
				->scalarNode('login_check')->isRequired()->end()
				->arrayNode('anonymous')
					->prototype('scalar')->end()
				->end()
			->end();

		return $definition;
	}

}
