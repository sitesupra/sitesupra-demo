<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Controller\Pages\Entity;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Markup;

/**
 * Parses supra markup tags inside the HTML content
 */
class ParsedHtmlFilter implements FilterInterface
{

	/**
	 * @var BlockProperty
	 */
	public $property;

	/**
	 * @var WriterAbstraction
	 */
	private $log;

	/**
	 * Create log instance
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}

	/**
	 * Parse supra.link, return beginning part of referenced link element.
	 * @param Entity\ReferencedElement\LinkReferencedElement $link
	 * @return string
	 */
	private function parseSupraLinkStart(Entity\ReferencedElement\LinkReferencedElement $link)
	{
		ObjectRepository::setCallerParent($link, $this);

		$attributes = array(
				'target' => $link->getTarget(),
				'title' => $link->getTitle(),
				'href' => $link->getUrl()
		);

		$tag = new \Supra\Html\HtmlTagStart('a');

		foreach ($attributes as $attributeName => $attributeValue) {

			if ($attributeValue != '') {
				$tag->setAttribute($attributeName, $attributeValue);
			}
		}
		
		if ($link->getResource() == Entity\ReferencedElement\LinkReferencedElement::RESOURCE_FILE) {
			$file = $link->getFile();
			
			if ( ! empty($file)) {
				$extension = $file->getExtension();
				$tag->addClass('file-' . mb_strtolower($extension));
			}
		}

		$html = $tag->toHtml();

		return $html;
	}

	/**
	 * Returns closing tag for referenced link element.
	 * @return string 
	 */
	private function parseSupraLinkEnd()
	{
		$tag = new \Supra\Html\HtmlTagEnd('a');

		return $tag->toHtml();
	}

	/**
	 * Parse supra.image
	 * @param Entity\ReferencedElement\ImageReferencedElement $imageData
	 * @return string
	 */
	private function parseSupraImage(Entity\ReferencedElement\ImageReferencedElement $imageData)
	{
		$html = null;
		$imageId = $imageData->getImageId();
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();
		$image = $em->find('Supra\FileStorage\Entity\Image', $imageId);

		if (empty($image)) {
			$this->log->warn("Image #{$imageId} has not been found");
		}
		else {
			//TODO: add other attributes as align, size, etc
			$sizeName = $imageData->getSizeName();
			$src = $fs->getWebPath($image, $sizeName);

			$tag = new \Supra\Html\HtmlTag('img');
			$tag->setAttribute('src', $src);

			$align = $imageData->getAlign();
			if ( ! empty($align)) {
				$tag->addClass('align-' . $align);
			}

			$tag->addClass($imageData->getStyle());

			$width = $imageData->getWidth();
			if ( ! empty($width)) {
				$tag->setAttribute('width', $width);
			}

			$height = $imageData->getHeight();
			if ( ! empty($height)) {
				$tag->setAttribute('height', $height);
			}

			//FIXME: now it applies for both – alt and title
			$title = $imageData->getAlternativeText();
			if ( ! empty($title)) {
				$tag->setAttribute('title', $title);
				$tag->setAttribute('alt', $title);
			}

			$html = $tag->toHtml();
		}

		return $html;
	}

	/**
	 * Replace image/link supra tags with real elements
	 * @param string $value
	 * @param Collection $metadata
	 * @return string 
	 */
	protected function parseSupraMarkup($value, Collection $metadata)
	{
		$tokenizer = new Markup\DefaultTokenizer($value);

		$tokenizer->tokenize();

		$result = array();

		foreach ($tokenizer->getElements() as $element) {

			if ($element instanceof Markup\HtmlElement) {
				$result[] = $element->getContent();
			}
			else if ($element instanceof Markup\SupraMarkupImage) {

				$metadataItem = $metadata[$element->getId()];

				if (empty($metadataItem)) {
					$this->log->warn("Referenced image element " . get_class($element) . "-" . $element->getId() . " not found for {$this->property}");
				}
				else {

					$image = $metadataItem->getReferencedElement();
					$result[] = $this->parseSupraImage($image);
				}
			}
			else if ($element instanceof Markup\SupraMarkupLinkStart) {

				$metadataItem = $metadata[$element->getId()];

				if (empty($metadataItem)) {
					$this->log->warn("Referenced link element " . get_class($element) . "-" . $element->getId() . " not found for {$this->property}");
				}
				else {

					$link = $metadataItem->getReferencedElement();
					$result[] = $this->parseSupraLinkStart($link);
				}
			}
			else if ($element instanceof Markup\SupraMarkupLinkEnd) {

				$result[] = $this->parseSupraLinkEnd();
			}
		}

		return join('', $result);
	}

	/**
	 * @param string $content
	 * @return string
	 */
	public function filter($content)
	{
		$value = $this->property->getValue();
		$metadata = $this->property->getMetadata();

		$filteredValue = $this->parseSupraMarkup($value, $metadata);

		return $filteredValue;
	}

}
