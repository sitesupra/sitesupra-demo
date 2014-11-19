<?php

namespace Supra\Package\Cms\Pages\Editable\Transformer;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class ImageEditorValueTransformer implements ValueTransformerInterface, BlockPropertyAware, ContainerAware
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
		if (empty($value)) {
			$this->property->getMetadata()
					->remove('image');

			return null;
		}

		$metadata = $this->property->getMetadata();

		if (! $metadata->offsetExists('image')) {
			$metadata->set('image', new BlockPropertyMetadata('image', $this->property));
		}

		$metaItem = $metadata->get('image');
		/* @var $metaItem BlockPropertyMetadata */

		$element = new ImageReferencedElement();

		// @TODO: some data validation must happen here.
		$element->fillArray(array(
			'image' => $value,
		));

		$metaItem->setReferencedElement($element);

		return null;
	}

	/**
	 * @param mixed $value
	 * @return null|array
	 */
	public function transform($value)
	{
		if ($value !== null) {
			// @TODO: not sure if this one is needed. just double checking.
			throw new \LogicException(
					'Expecting image containing block property value to be null.'
			);
		}

		if ($this->property->getMetadata()->offsetExists('image')) {
			
			$metaItem = $this->property->getMetadata()->get('image');

			$element = $metaItem->getReferencedElement();

			if ($element instanceof ImageReferencedElement) {

				$fileStorage = $this->container['cms.file_storage'];
				/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */
				$image = $fileStorage->findImage($element->getImageId());

				if ($image !== null) {
					return array_merge(
							array('image' => $fileStorage->getFileInfo($image)),
							$element->toArray()
					);
				}
			}
		}

		return null;
	}

	/**
	 * @param ContainerInterface $container
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}
	
}
