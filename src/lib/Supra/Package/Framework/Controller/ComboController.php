<?php

namespace Supra\Package\Framework\Controller;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\CssRewriteFilter;

class ComboController extends Controller
{
	public function comboAction(Request $request)
	{
		$paths = explode('&', $request->attributes->get('paths'));

		$javaScriptAssets = $cssAssets
				= array();
		
		$webRoot = $this->container->getApplication()->getWebRoot();

		//@todo: support multiple assets instead of hack above
		//@todo: compress / filter assets
		foreach ($paths as $asset) {

			$assetPath = $webRoot . DIRECTORY_SEPARATOR .  $asset;

			if (($extension = pathinfo($assetPath, PATHINFO_EXTENSION)) === 'css'
					|| $extension === 'less') {

				if ($extension === 'css'
						&& ! file_exists($assetPath)
						&& file_exists($assetPath . '.less')) {

					$assetPath = $assetPath . '.less';
				}

				$cssAssets[] = new FileAsset($assetPath, array(), $webRoot);
				
				continue;
			}

			$javaScriptAssets[] = new FileAsset($assetPath, array(), $webRoot);
		}

		if (empty($javaScriptAssets) && empty($cssAssets)) {
			throw new ResourceNotFoundException();

		} elseif (! empty($javaScriptAssets) && ! empty($cssAssets)) {
			
			throw new \LogicException('You cannot request combination of different asset types.');
		}

		$collection = ! empty($javaScriptAssets)
				? new AssetCollection($javaScriptAssets)
				: new AssetCollection($cssAssets, array(new LessphpFilter, new CssRewriteFilter));

		$content = $this->container->getCache()->fetch('combo', $paths, function () use ($collection) {
			return $collection->dump();
		}, $collection->getLastModified());

		$contentType = ! empty($javaScriptAssets) ? 'text/javascript' : 'text/css';

		return new Response($content, 200, array('Content-Type' => $contentType));
	}

}
