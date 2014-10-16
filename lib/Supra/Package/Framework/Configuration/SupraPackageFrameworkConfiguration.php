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
					->append($this->getDoctrineDefinition())
					->append($this->getServicesDefinition())
					->append($this->getSessionDefinition())
				->end();

		return $treeBuilder;
	}

	public function getSessionDefinition()
	{
		$definition = new ArrayNodeDefinition('session');

		$definition->children()
				->scalarNode('storage')->isRequired()->end()
			->end();

		return $definition;
	}

	public function getDoctrineDefinition()
	{
		$definition = new ArrayNodeDefinition('doctrine');

		$definition->children()
				->arrayNode('credentials')
					->children()
						->scalarNode('hostname')->isRequired()->end()
						->scalarNode('username')->isRequired()->end()
						->scalarNode('password')->isRequired()->end()
						->scalarNode('charset')->isRequired()->end()
						->scalarNode('database')->isRequired()->end()
					->end()
				->end()
				->arrayNode('event_managers')
					->isRequired()
					->requiresAtLeastOneElement()
					->prototype('array')
						->children()
							->arrayNode('subscribers')
								->prototype('scalar')->end()
							->end()
						->end()
					->end()
				->end()
				->arrayNode('connections')
					->isRequired()
					->requiresAtLeastOneElement()
					->prototype('array')
						->children()
							->scalarNode('host')->isRequired()->end()
							->scalarNode('user')->isRequired()->end()
							->scalarNode('password')->isRequired()->end()
							->scalarNode('dbname')->isRequired()->end()
							->scalarNode('driver')->isRequired()->end()
							->scalarNode('charset')->isRequired()->end()
							->scalarNode('event_manager')->isRequired()->end()
						->end()
					->end()
				->end()
				->arrayNode('configuration')
					->children()
						->arrayNode('types')
							->prototype('variable')->end()
						->end()
						->arrayNode('type_overrides')
							->prototype('variable')->end()
						->end()
						->arrayNode('hydrators')
							->prototype('variable')->end()
						->end()
					->end()
				->end()
				->arrayNode('entity_managers')
					->prototype('array')
						->children()
							->scalarNode('connection')->isRequired()->end()
							->scalarNode('event_manager')->isRequired()->end()
						->end()
					->end()
				->end()
				->scalarNode('default_entity_manager')->isRequired()->end()
				->scalarNode('default_connection')->isRequired()->end()
			->end();

		return $definition;
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
