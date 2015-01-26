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
