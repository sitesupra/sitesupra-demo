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

namespace Supra\Package\Cms\Pages\Editable\Transformer;

use MediaEmbed\MediaEmbed;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Exception\TransformationFailedException;
use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Package\Cms\Entity\ReferencedElement\MediaReferencedElement;
use Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class HtmlEditorValueTransformer implements ValueTransformerInterface, ContainerAware, BlockPropertyAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var BlockProperty
	 */
	protected $property;

	/**
	 * @param BlockProperty $blockProperty
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->property = $blockProperty;
	}

	public function reverseTransform($value)
	{
		// used fonts list
		// @FIXME: sanitize list
		$fonts = ! empty($value['fonts']) ? $value['fonts'] : array();

		$this->getFontsMetadata()
				->setValue(serialize($fonts));

		// additional metadata
		$metadata = $this->property->getMetadata();

		// @TODO: not performance-wise.
		$metadata->clear();

		if (! empty($value['data'])) {
			foreach ($value['data'] as $name => $itemData) {

				if (empty($itemData['type'])) {
					throw new TransformationFailedException(sprintf(
						'No type specified for HTML metadata element [%s].', $name
					));
				}

				$element = null;

				switch ($itemData['type']) {
					case ImageReferencedElement::TYPE_ID:
					case LinkReferencedElement::TYPE_ID:
						$element = ReferencedElementAbstract::fromArray($itemData);
						break;

					case 'video': // @TODO: BC. Remove.
					case MediaReferencedElement::TYPE_ID:

						if (empty($itemData['url'])) {
							throw new TransformationFailedException(sprintf(
								'No media URL specified for item [%s].', $name
							));
						}

						$mediaEmbed = $this->container['cms.media_embed'];
						/* @var $mediaEmbed MediaEmbed */

						$mediaObject = $mediaEmbed->parseUrl($itemData['url']);

						if ($mediaObject === null) {
							throw new TransformationFailedException(sprintf(
								'Failed to parse media URL [%s].', $itemData['url']
							));
						}

						$element = new MediaReferencedElement();
						$element->setUrl($itemData['url']);

						// width attribute
						if (isset($itemData['width'])) {

							$width = (int) $itemData['width'];

							if ($width < 1 ) {
								throw new TransformationFailedException(sprintf(
									'Invalid width value: [%s]', $itemData['width']
								));
							}

							$element->setWidth($width);
						}

						// height attribute
						if (isset($itemData['height'])) {

							$height = (int) $itemData['height'];

							if ($height < 1 ) {
								throw new TransformationFailedException(sprintf(
									'Invalid height value: [%s]', $itemData['height']
								));
							}

							$element->setHeight($height);
						}

						break;

					default:
						throw new TransformationFailedException(sprintf(
							'Unrecognized HTML metadata element type [%s].', $itemData['type']
						));
				}

				$metaItem = new BlockPropertyMetadata($name, $this->property);

				$metaItem->setReferencedElement($element);

				$metadata->set($name, $metaItem);
			}
		}

		// return HTML as is
		return ! empty($value['html']) ? $value['html'] : null;
	}

	/**
	 * @param mixed $value
	 * @return array
	 */
	public function transform($value)
	{
		$fontString = $this->getFontsMetadata()
				->getValue();

		$fontsArray = ! empty($fontString) ? unserialize($fontString) : array();

		$referencedElementData = array();

		foreach ($this->property->getMetadata() as $name => $metadata) {
			/* @var $metadata BlockPropertyMetadata */
			$referencedElement = $metadata->getReferencedElement();

			if ($referencedElement !== null) {
				$referencedElementData[$name] = $this->convertReferencedElementToArray($referencedElement);
			}
		}

		return array(
			'fonts' => $fontsArray,
			'html'	=> $value,
			'data'	=> $referencedElementData,
		);
	}

	/**
	 * @return BlockPropertyMetadata
	 */
	private function getFontsMetadata()
	{
		$metaCollection = $this->property->getMetadata();

		if ($metaCollection->containsKey('fonts')) {
			return $metaCollection->get('fonts');
		}

		return new BlockPropertyMetadata('fonts', $this->property);
	}

	/**
	 * @param ReferencedElementAbstract $element
	 * @return array
	 */
	private function convertReferencedElementToArray(ReferencedElementAbstract $element)
	{
		$elementData = $element->toArray();

		// we need to provide image data in addition for Image elements
		if ($element instanceof ImageReferencedElement) {

			$fileStorage = $this->container['cms.file_storage'];
			/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */
			$image = $fileStorage->findImage($element->getImageId());

			if ($image === null) {
				// @TODO: not sure we should return anything.
				return array();
			}

			$elementData['image'] = $fileStorage->getFileInfo($image);
		}

		return $elementData;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
}