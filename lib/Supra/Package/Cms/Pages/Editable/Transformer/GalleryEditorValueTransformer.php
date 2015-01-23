<?php

namespace Supra\Package\Cms\Pages\Editable\Transformer;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;
use Supra\Package\Cms\Editable\Exception\TransformationFailedException;

class GalleryEditorValueTransformer implements ValueTransformerInterface, ContainerAware, BlockPropertyAware
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
	 * {@inheritDoc}
	 */
	public function reverseTransform($value)
	{
		$metadataCollection = $this->property->getMetadata();

		// @FIXME: absolutely not performance-wise
		$metadataCollection->clear();

		if ($value && is_array($value)) {

			// @TODO: some input data validation is needed.

			foreach (array_values($value) as $key => $itemData) {

				if (! isset($itemData['image']) || ! is_array($itemData['image'])) {
					throw new TransformationFailedException(sprintf(
						'Missing image data for item [%s]', $key)
					);
				}

				$imageData = $itemData['image'];

				if (empty($imageData['id'])) {
					throw new TransformationFailedException(sprintf(
						'Image ID is missing for element [%s].', $key
					));
				}

				$image = $this->getFileStorage()->findImage($imageData['id']);

				if ($image === null) {
					throw new TransformationFailedException(sprintf(
							'Image [%s] not found in file storage.', $imageData['id']
					));
				}

				$element = new ImageReferencedElement();

				$element->fillFromArray($imageData);

				if (! empty($itemData['title'])) {
					$element->setTitle($itemData['title']);
				}

				if (! empty($itemData['description'])) {
					$element->setDescription($itemData['description']);
				}

				$metadataCollection->set($key, new BlockPropertyMetadata($key, $this->property, $element));
			}
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function transform($value)
	{
		$imageDataArray = array();

		$fileStorage = $this->getFileStorage();

		foreach ($this->property->getMetadata() as $metadata) {
			/* @var $metadata BlockPropertyMetadata */
			$element = $metadata->getReferencedElement();

			if (! $element instanceof ImageReferencedElement) {
				throw new TransformationFailedException(sprintf(
						'Expecting only image referenced elements, [%s] received.',
						($element ? get_class($element) : 'NULL')
				));
			}

			$image = $fileStorage->findImage($element->getImageId());

			if ($image === null) {
				$this->container->getLogger()->warn(
						"Image [{$element->getImageId()}] not found."
				);
				continue;
			}

			$imageDataArray[] = array(
				'title' => $element->getTitle(),
				'description' => $element->getDescription(),
				'image' => array_merge(
					$element->toArray(),
					array('image' => $fileStorage->getFileInfo($image))
				)
			);
		}

		return $imageDataArray;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param BlockProperty $blockProperty
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->property = $blockProperty;
	}

	/**
	 * @return \Supra\Package\Cms\FileStorage\FileStorage
	 */
	private function getFileStorage()
	{
		return $this->container['cms.file_storage'];
	}
	
}