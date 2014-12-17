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
					->append($this->getAuditDefinition())
					->append($this->getSessionDefinition())
					->append($this->getMailerDefinition())
					->append($this->getServicesDefinition())
				->end();

		return $treeBuilder;
	}

	public function getAuditDefinition()
	{
		$definition = new ArrayNodeDefinition('doctrine_audit');

		$definition->children()
				->arrayNode('entities')
					->prototype('scalar')->end()
				->end()
				->arrayNode('ignore_columns')
					->prototype('scalar')->end()
				->end()
			->end();

		return $definition;
	}

	public function getMailerDefinition()
	{
		$definition = new ArrayNodeDefinition('swiftmailer');

		$definition->children()
				->arrayNode('mailers')
					->prototype('array')
						->children()
							->scalarNode('transport')->isRequired()->end()
							->arrayNode('params')
								->prototype('scalar')->end()
							->end()
						->end()
					->end()
				->end()
				->scalarNode('default')->isRequired()->end()
				->scalarNode('default_from')->isRequired()->end()
			->end();

		return $definition;
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
							->scalarNode('configuration')->defaultValue('default')->end()
						->end()
					->end()
				->end()
				->arrayNode('types')
					->prototype('variable')->end()
				->end()
				->arrayNode('type_overrides')
					->prototype('variable')->end()
				->end()
				->arrayNode('configurations')
					->isRequired()
					->requiresAtLeastOneElement()
					->prototype('array')
						->children()
							->arrayNode('hydrators')
								->prototype('variable')->end()
							->end()
						->end()
					->end()
				->end()
				->arrayNode('entity_managers')
					->prototype('array')
						->children()
							->scalarNode('connection')->isRequired()->end()
							->scalarNode('event_manager')->isRequired()->end()
							->scalarNode('configuration')->defaultValue('default')->end()
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
				->useAttributeAsKey('id')
				->prototype('array')
					->children()
						//->scalarNode('id')->isRequired()->end()
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
