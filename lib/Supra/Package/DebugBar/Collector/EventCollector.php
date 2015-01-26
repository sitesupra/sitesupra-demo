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

namespace Supra\Package\DebugBar\Collector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class EventCollector extends DataCollector implements Renderable, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @param \Supra\Core\DependencyInjection\ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 *
	 * @return array Collected data
	 */
	public function collect()
	{
		$events = $this->container->getEventDispatcher()->getEventTrace();

		$dataFormatter = $this->getDataFormatter();

		return array_map (function ($value) use ($dataFormatter) {
			$value = array(
				'name' => $value['name'],
				'timestamp' => $value['timestamp'],
				'listeners' => $value['listeners'],
				'event' => $value['event'] ? get_class($value['event']) : 'unknown'
			);

			return $dataFormatter->formatVar($value);
		}, $events);
	}

	/**
	 * Returns the unique name of the collector
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'events';
	}

	/**
	 * Returns a hash where keys are control names and their values
	 * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
	 *
	 * @return array
	 */
	public function getWidgets()
	{
		$name = $this->getName();
		return array(
			"$name" => array(
				"icon" => "gear",
				"widget" => "PhpDebugBar.Widgets.VariableListWidget",
				"map" => "$name",
				"default" => "{}"
			)
		);
	}

}