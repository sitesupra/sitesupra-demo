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

		foreach ($this->container->getParameter('cms.cms_resources.css_pack') as $asset) {
			$assetPath = $this->container->getApplication()->getWebRoot().DIRECTORY_SEPARATOR.$asset;
			$assetObject = new FileAsset($assetPath, array(), $this->container->getApplication()->getWebRoot());
			$assetObject->setTargetPath('/_cms_internal/');

			if (substr($asset, strrpos($asset, '.')) == '.less') {
				$assetObject->ensureFilter(new LessphpFilter());
			}

			$collection->add($assetObject);
		}

		$content = $this->container->getCache()->fetch('cms_assets', 'css_pack', function () use ($collection) {
			return $collection->dump(new CssRewriteFilter());
		}, $collection->getLastModified());

		return new Response($content, 200, array('Content-Type' => 'text/css'));
	}

	public function jsPackAction()
	{
		$collection = new AssetCollection();

		foreach ($this->container->getParameter('cms.cms_resources.js_pack') as $asset) {
			$collection->add(new FileAsset($asset));
		}

		$content = $this->container->getCache()->fetch('cms_assets', 'js_pack', function () use ($collection) {
			return $collection->dump();
		}, $collection->getLastModified());

		return new Response($content, 200, array('Content-Type' => 'text/javascript'));
	}
}