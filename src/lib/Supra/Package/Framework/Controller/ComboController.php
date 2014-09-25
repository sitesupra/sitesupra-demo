<?php

namespace Supra\Package\Framework\Controller;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ComboController extends Controller
{
	public function comboAction(Request $request)
	{
		$collection = new AssetCollection();

		$paths = $request->attributes->get('paths');

		$paths = explode('&', $paths);

		//@todo: support multiple assets instead of hack above
		//@todo: compress / filter assets
		foreach ($paths as $asset) {
			$asset = $this->container->getApplication()->getWebRoot().DIRECTORY_SEPARATOR.$asset;
			$collection->add(new FileAsset($asset, array(), $this->container->getApplication()->getWebRoot()));
		}

		$content = $this->container->getCache()->fetch('combo', $paths, function () use ($collection) {
			return $collection->dump();
		}, $collection->getLastModified());

		return new Response($content, 200, array('Content-Type' => 'text/javascript'));

	}

}
