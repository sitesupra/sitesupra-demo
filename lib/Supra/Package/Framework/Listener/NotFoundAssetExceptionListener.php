<?php

namespace Supra\Package\Framework\Listener;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\LessphpFilter;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Core\Event\RequestResponseEvent;
use Supra\Core\Event\RequestResponseListenerInterface;
use Symfony\Component\HttpFoundation\Response;

class NotFoundAssetExceptionListener implements RequestResponseListenerInterface, ContainerAware
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
	 * @param RequestResponseEvent $event
	 */
	public function listen(RequestResponseEvent $event)
	{
		$path = $event->getRequest()->getPathInfo();

		$parts = pathinfo($path);

		$parts = array_merge(array(
			'dirname' => '',
			'basename' => '',
			'extension' => '',
			'filename'
		), $parts);

		switch (strtolower($parts['extension'])) {
			case 'css':
				//possible it's not yet compiled less file?
				$lessFile = $this->container->getApplication()->getWebRoot().$path.'.less';

				if (is_file($lessFile)) {
					$asset = new FileAsset($lessFile);
					$asset->ensureFilter(new LessphpFilter());

					$content = $this->container->getCache()->fetch('assets_404', $path, function () use ($asset) {
						return $asset->dump();
					}, $asset->getLastModified());

					$event->setResponse(new Response($content, 200, array('Content-Type' => 'text/css')));
					return;
				}
				break;
		}
	}

}
