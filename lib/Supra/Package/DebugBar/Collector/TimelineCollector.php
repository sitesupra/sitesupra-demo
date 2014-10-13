<?php

namespace Supra\Package\DebugBar\Collector;

use DebugBar\DataCollector\TimeDataCollector;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\ControllerEvent;
use Supra\Core\Event\DataAgnosticEvent;
use Supra\Core\Event\KernelEvent;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Supra;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

class TimelineCollector implements ContainerAware, EventSubscriberInterface
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public static function getSubscribedEvents()
	{
		return array(
			KernelEvent::REQUEST => array('request'),
			KernelEvent::RESPONSE => array('response'),
			Supra::EVENT_CONTAINER_BUILD_COMPLETE => array('containerDone'),
			Supra::EVENT_BOOT_START => array('bootStart'),
			Supra::EVENT_BOOT_END => array('bootEnd'),
			Supra::EVENT_SHUTDOWN_START => array('shutdownStart'),
			Supra::EVENT_SHUTDOWN_END => array('shutdownEnd'),
			KernelEvent::CONTROLLER_START => array('controllerStart'),
			KernelEvent::CONTROLLER_END => array('controllerEnd')
		);
	}

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function controllerStart(ControllerEvent $event)
	{
		$this->getTimeCollector()->startMeasure($this->formatControllerId($event), 'Controller: ' . $this->formatController($event));
	}

	public function controllerEnd(ControllerEvent $event)
	{
		$this->getTimeCollector()->stopMeasure($this->formatControllerId($event));
	}

	public function bootStart()
	{
		$this->getTimeCollector()->startMeasure('supra_boot', 'Supra boot');
	}

	public function bootEnd()
	{
		$this->getTimeCollector()->stopMeasure('supra_boot');
	}

	public function shutdownStart()
	{
		$this->getTimeCollector()->startMeasure('supra_shutdown', 'Supra shutdown');
	}

	public function shutdownEnd()
	{
		$this->getTimeCollector()->startMeasure('supra_shutdown');
	}

	public function containerDone(DataAgnosticEvent $event)
	{
		$this->getTimeCollector()->addMeasure(
			'Container build, supra up',
			$this->getTimeCollector()->getRequestStartTime(),
			microtime(true)
		);
	}

	/**
	 * @param RequestResponseEvent $event
	 */
	public function request(RequestResponseEvent $event)
	{
		$request = $event->getRequest();

		if ($request instanceof Request) {
			$this->getTimeCollector()->startMeasure(
				'request_' . spl_object_hash($request),
				$this->formatRequest($request)
			);
		}
	}

	public function response(RequestResponseEvent $event)
	{
		$request = $event->getResponse();

		if ($request instanceof Request) {
			$this->getTimeCollector()->stopMeasure(
				'request_' . spl_object_hash($request)
			);
		}
	}

	protected function formatController(ControllerEvent $event)
	{
		return get_class($event->getController()) . ':' . $event->getAction();
	}

	protected function formatControllerId(ControllerEvent $event)
	{
		return 'controller_'.md5($this->formatController($event));
	}

	protected function formatRequest(Request $request)
	{
		$text = 'Request: '.$request->getPathInfo();

		if ($request->attributes->has('_controller')) {
			$text .= ' (' . $request->attributes->get('_controller');

			if ($request->attributes->has('_action')) {
				$text .= ':'.$request->attributes->get('_action');
			}

			$text .= ')';
		}

		return $text;
	}

	/**
	 * @return TimeDataCollector
	 */
	protected function getTimeCollector()
	{
		return $this->container['debug_bar.debug_bar']['time'];
	}

}
