<?php

/**
 * @FIXME: this depends on Pages specific stuff, move under Pages NS!
 */
namespace Supra\Package\Cms\Editable\Transformer;

use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;

class HtmlEditorValueTransformer implements ValueTransformerInterface
{
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
		if (! empty($value['data'])) {
			foreach ($value['data'] as $itemData) {
				// convert to referenced element.
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

		// @fixme: convert to array

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
}

//	/**
//	 * Converts referenced element to JS array
//	 * @param ReferencedElement\ReferencedElementAbstract $element
//	 * @return array
//	 */
//	protected function convertReferencedElementToArray(ReferencedElement\ReferencedElementAbstract $element)
//	{
//		$fileData = array();
//
//		$storage = ObjectRepository::getFileStorage($this);
//
//		if ($element instanceof ReferencedElement\LinkReferencedElement) {
//
//			if ($element->getResource() == ReferencedElement\LinkReferencedElement::RESOURCE_FILE) {
//
//				$fileId = $element->getFileId();
//
//				if ( ! empty($fileId)) {
//
//					$file = $storage->find($fileId, File::CN());
//
//					if ( ! is_null($file)) {
//						$fileInfo = $storage->getFileInfo($file);
//						$fileData['file_path'] = $fileInfo['path'];
//					}
//				}
//			}
//		}
//
//		else if ($element instanceof ReferencedElement\ImageReferencedElement) {
//
//			$imageId = $element->getImageId();
//
//			if ( ! empty($imageId)) {
//				$image = $storage->find($imageId, Image::CN());
//
//				if ( !is_null($image)) {
//					$info = $storage->getFileInfo($image);
//					$fileData['image'] = $info;
//				}
//			}
//		}
//
//		else if ($element instanceof ReferencedElement\IconReferencedElement) {
//
//			$iconId = $element->getIconId();
//
//			$themeConfiguration = \Supra\ObjectRepository\ObjectRepository::getThemeProvider($this)
//					->getCurrentTheme()
//					->getConfiguration();
//
//			$iconConfiguration = $themeConfiguration->getIconConfiguration();
//			if ($iconConfiguration instanceof \Supra\Controller\Layout\Theme\Configuration\ThemeIconSetConfiguration) {
//				$fileData['svg'] = $iconConfiguration->getIconSvgContent($iconId);
//			}
//
//		}
//
//		$data = $fileData + $element->toArray();
//
//		return $data;
//	}