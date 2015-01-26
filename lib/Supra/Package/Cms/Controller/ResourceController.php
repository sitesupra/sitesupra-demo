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

namespace Supra\Package\Cms\Controller;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Filter\CssRewriteFilter;
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