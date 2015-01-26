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

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Html\HtmlTag;

class InlineMapFilter implements FilterInterface
{
	public function filter($content, array $options = array())
	{
		$mapData = array(
			'latitude'	=> 56.946744,
			'longitude' => 24.098560,
			'zoom'		=> 12,
			'height'	=> 200,
		);

		if (! empty($content)) {

			$content = unserialize($content);

			if (is_array($content)) {
				$mapData = array_merge($mapData, array(
					'latitude'	=> $content['latitude'],
					'longitude' => $content['longitude'],
					'zoom'		=> $content['zoom'],
					'height'	=> $content['height'],
				));
			}
		}

		$tag = new HtmlTag('div');
		$tag->forceTwoPartTag(true);

		$tag->setAttributes(array(
			'class'					=> 'map',
			'data-refresh-event'	=> 'googleMap',
			'data-require'			=> 'plugins/blocks/google-map',
			'data-attach'			=> '$.fn.googleMap',
			'data-latitude'			=> $mapData['latitude'],
			'data-longitude'		=> $mapData['longitude'],
			'data-zoom'				=> $mapData['zoom'],
		));

		if (! empty($mapData['height'])) {
			$tag->setAttribute('style', 'height: ' . (int) $mapData['height'] . 'px');
		}

		// @TODO: if height is supported natively, marker text input also could be part of map editable.
		if (! empty($options['markerText'])) {
			$tag->setAttribute('data-marker-text', $options['markerText']);
		}

		return $tag;
 	}
}
