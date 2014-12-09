<?php

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

		// @TODO: if height is supported natviely, marker text input also could be part of map editable.
		if (! empty($options['markerText'])) {
			$tag->setAttribute('data-marker-text', $options['markerText']);
		}

		return $tag;
 	}
}
