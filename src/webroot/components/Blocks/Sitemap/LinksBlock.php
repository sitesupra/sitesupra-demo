<?php

namespace Project\Blocks\Sitemap;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Editable;

/**
 * LinksBlock
 */
abstract class LinksBlock extends BlockController
{

	public static $linkCount = 8;

	public function getPropertyDefinition($prefix = 'link', $title = 'Link', $count = null, $groupLabel = null)
	{
		if (is_null($count)) {
			$count = static::$linkCount;
		}

		$properties = array();

		for ($i = 1; $i <= $count; $i ++ ) {
			$link = new Editable\Link("$title #$i", $groupLabel);
			$properties["{$prefix}_{$i}"] = $link;
		}

		return $properties;
	}

	/**
	 * @param string $id
	 * @return LinkReferencedElement
	 */
	protected function getLink($id)
	{
		$property = $this->getPropertyValue($id);

		if ($property instanceof LinkReferencedElement) {
			$url = $property->getUrl();

			if ( ! empty($url)) {
				return $property;
			}
		}
	}

	protected function getLinks($prefix = 'link', $count = null)
	{
		if (is_null($count)) {
			$count = static::$linkCount;
		}
		$links = array();

		for ($i = 1; $i <= $count; $i ++ ) {
			$property = $this->getLink("{$prefix}_{$i}");

			if ( ! empty($property)) {
				$links[] = $property;
			}
		}

		return $links;
	}

	/**
	 * Returns children links for the selected page
	 * @param LinkReferencedElement $link
	 * @return array
	 */
	protected function getChildLinks(LinkReferencedElement $link = null)
	{
		$return = array();

		if (empty($link)) {
			return array();
		}

		$localization = $link->getPage();

		if (empty($localization)) {
			return array();
		}

		$children = $localization->getPublicChildren();

		foreach ($children as $child) {
			/* @var $child PageLocalization */

			if ( ! $child->isVisibleInMenu()) {
				continue;
			}

			$link = new LinkReferencedElement();
			$link->setPageLocalization($child);
			$link->setResource(LinkReferencedElement::RESOURCE_PAGE);

			$return[] = $link;
		}

		return $return;
	}

}