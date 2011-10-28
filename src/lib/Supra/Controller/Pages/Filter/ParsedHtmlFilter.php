<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Controller\Pages\Entity;
use Doctrine\Common\Collections\Collection;

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
	 * Parse supra.link
	 * @param string $content
	 * @param Entity\ReferencedElement\LinkReferencedElement $link
	 * @return string
	 */
	private function parseSupraLink($content, Entity\ReferencedElement\LinkReferencedElement $link)
	{
		ObjectRepository::setCallerParent($link, $this);
		$url = $link->getUrl();
		$target = $link->getTarget();
		$title = $link->getTitle();

		$attributes = array(
			'target' => $link->getTarget(),
			'title' => $link->getTitle(),
			'href' => $url
		);

		$tag = new \Supra\Html\HtmlTag('a', $content);

		foreach ($attributes as $attributeName => $attributeValue) {
			if ($attributeValue != '') {
				$tag->setAttribure($attributeName, $attributeValue);
			}
		}
		
		$text = $tag->toHtml();

		return $text;
	}
	
	/**
	 * Parse supra.image
	 * @param string $content
	 * @param Entity\ReferencedElement\ImageReferencedElement $imageData
	 * @return string
	 */
	private function parseSupraImage($content, Entity\ReferencedElement\ImageReferencedElement $imageData)
	{
		$html = null;
		$imageId = $imageData->getImageId();
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();
		$image = $em->find('Supra\FileStorage\Entity\Image', $imageId);

		if (empty($image)) {
			$this->log->warn("Image #{$imageId} has not been found");
		} else {
			//TODO: add other attributes as align, size, etc
			$sizeName = $imageData->getSizeName();
			$src = $fs->getWebPath($image, $sizeName);
			
			$tag = new \Supra\Html\HtmlTag('img');
			$tag->setAttribure('src', $src);
			
			$classNames = array();
			
			$align = $imageData->getAlign();
			if ( ! empty($align)) {
				$tag->addClass('align-' . $align);
			}
			
			$tag->addClass($imageData->getStyle());
			
			$width = $imageData->getWidth();
			if ( ! empty($width)) {
				$tag->setAttribure('width', $width);
			}

			$height = $imageData->getHeight();
			if ( ! empty($height)) {
				$tag->setAttribure('height', $height);
			}
			
			//FIXME: now it applies for both – alt and title
			$title = $imageData->getAlternativeText();
			if ( ! empty($title)) {
				$tag->setAttribure('title', $title);
				$tag->setAttribure('alt', $title);
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
		//TODO: dummy replace for links, images only for now, must move to some filters, suppose like template engine extensions
		//Also this doesn't allow nested tags, but there cannot be link inside the link so it's not a problem now.
		$matches = array();
		preg_match_all('/\{supra\.([^\s]+) id="(.*?)"\}((.*?)(\{\/supra\.\1\}))?/', $value, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		$offset = 0;
		$result = '';

		foreach ($matches as $match) {

			$offsetInit = $match[0][1];
			$offsetEnd = $match[0][1] + strlen($match[0][0]);

			$class = $match[1][0];
			$id = $match[2][0];
			$content = $match[4][0];
			
			// Case of image inside the link
			$content = $this->parseSupraMarkup($content, $metadata);

			$metadataItem = $metadata->get($id);
			
			if (is_null($metadataItem)) {
				//WARN
			} else {
			
				$referencedElement = $metadataItem->getReferencedElement();
				$text = '';

				switch ($class) {
					case Entity\ReferencedElement\LinkReferencedElement::TYPE_ID:
						if ($referencedElement instanceof Entity\ReferencedElement\LinkReferencedElement) {
							$text = $this->parseSupraLink($content, $referencedElement);
						} else {
							$this->log->warn("Referenced element {$class}-{$id} not found for {$this->property}");
						}
						break;
					case Entity\ReferencedElement\ImageReferencedElement::TYPE_ID:
						if ($referencedElement instanceof Entity\ReferencedElement\ImageReferencedElement) {
							$text = $this->parseSupraImage($content, $referencedElement);
						} else {
							$this->log->warn("Referenced element {$class}-{$id} not found for {$this->property}");
						}
						break;
					default:
						$this->log->warn("Unrecognized supra html markup tag {$class}-{$id} with data ", $referencedElement);
				}

				$result .= substr($value, $offset, $offsetInit - $offset);
				$result .= $text;

				$offset = $offsetEnd;
			}
		}

		$result .= substr($value, $offset);

		return $result;
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
