<?php

namespace Supra\Package\Cms\Controller;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\JSMinFilter;
use Assetic\Filter\LessFilter;
use Assetic\Filter\LessphpFilter;
use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ResourceController extends Controller
{
	public function cssPackAction()
	{
		$collection = new AssetCollection();

		foreach ($this->container->getParameter('cms.css_pack') as $asset) {
			$assetObject = new FileAsset($asset);

			if (substr($asset, strrpos($asset, '.')) == '.less') {
				$assetObject->ensureFilter(new LessphpFilter());
			}

			$collection->add($assetObject);
		}

		return new Response($collection->dump(), 200, array('Content-Type' => 'text/css'));
	}

	public function jsPackAction()
	{
		$collection = new AssetCollection();

		foreach ($this->container->getParameter('cms.js_pack') as $asset) {
			$collection->add(new FileAsset($asset));
		}

		return new Response($collection->dump(), 200, array('Content-Type' => 'text/javascript'));
	}
}