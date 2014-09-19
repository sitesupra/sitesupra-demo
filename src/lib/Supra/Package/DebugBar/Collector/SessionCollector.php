<?php

namespace Supra\Package\DebugBar\Collector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;

class SessionCollector extends DataCollector implements Renderable, ContainerAware
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
		$request = $this->container->getRequest();

		if (!$request->hasSession()) {
			return array('session' => false);
		} else {
			$dataFormatter = $this->getDataFormatter();

			return array_map (function ($value) use ($dataFormatter) {
				return $dataFormatter->formatVar($value);
			}, $request->getSession()->all());
		}
	}

	/**
	 * Returns the unique name of the collector
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'session';
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