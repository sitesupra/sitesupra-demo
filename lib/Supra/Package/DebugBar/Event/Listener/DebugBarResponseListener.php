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
		$request = $event->getRequest();
		$response = $event->getResponse();

		if ($request->isXmlHttpRequest()) {
			//handle with ajax
			$debugBar = $this->container['debug_bar.debug_bar'];

			$response->headers->add($debugBar->getDataAsHeaders());
		} else {
			//replace http response
			if ($response->headers->get('content-type') &&
				$response->headers->get('content-type') != 'text/html') {
				return;
			}

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

			$response->setContent($body);
		}
	}

}