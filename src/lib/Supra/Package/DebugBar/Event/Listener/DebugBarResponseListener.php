<?php

namespace Supra\Package\DebugBar\Event\Listener;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;

class DebugBarResponseListener implements ContainerAware, RequestResponseListenerInterface
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param RequestResponseEvent $event
	 */
	public function listen(RequestResponseEvent $event)
	{
		$debugBar = $this->container['debug_bar.debug_bar'];

		$renderer = $debugBar->getJavascriptRenderer();
		/* @var $renderer \DebugBar\JavascriptRenderer */
		$renderer->setBaseUrl('/public/debugbar');

		$body = $event->getResponse()->getContent();

		$body = str_ireplace(
			array('</head>', '</body>'),
			array(
				$renderer->renderHead() . PHP_EOL . '</head>',
				$renderer->render() . PHP_EOL . '</body>',
			),
			$body
		);

		$event->getResponse()->setContent($body);
	}

}