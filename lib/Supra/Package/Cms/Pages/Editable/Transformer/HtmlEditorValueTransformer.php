<?php

namespace Supra\Package\Cms\Pages\Editable\Transformer;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Transformer\ValueTransformerInterface;
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
		foreach ($metadata as $metaItem) {
			$metadata->removeElement($metaItem);
		}

		if (! empty($value['data'])) {
			foreach ($value['data'] as $name => $itemData) {

				$referencedElement = ReferencedElementAbstract::fromArray($itemData);

				$metaItem = new BlockPropertyMetadata($name, $this->property);

				$metaItem->setReferencedElement($referencedElement);

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