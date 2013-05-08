<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Controller\Pages\Entity;
use Supra\FileStorage\Entity\Image;
use Twig_Markup;

/**
 *
 */
class InlineMediaFilter implements FilterInterface
{
	/**
	 * @var BlockProperty
	 */
	public $property;

	/**
	 * Parse supra.image
	 * @param Entity\ReferencedElement\ImageReferencedElement $imageData
	 * @return string
	 */
	protected function parseSupraImage(Entity\ReferencedElement\ImageReferencedElement $imageData)
	{
		$html = null;
		$imageId = $imageData->getImageId();
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();
		$image = $em->find(Image::CN(), $imageId);
				
		if ( ! $image instanceof Image) {
			//$this->log->warn("Image #{$imageId} has not been found");
		}
		else {
			$sizeName = $imageData->getSizeName();
			$size = $image->findImageSize($sizeName);
			
			if ( ! $size) {
				//$this->log->warn("Image #{$imageId} size $sizeName has not been found");
				return;
			}
			
			$tag = new \Supra\Html\HtmlTag('img');
			$src = $width = $height = null;
			
			if ($size->isCropped()) {
				$width = $size->getCropWidth();
				$height = $size->getCropHeight();
			} else {
				$width = $size->getWidth();
				$height = $size->getHeight();
			}
			
			$src = $fs->getWebPath($image, $size);
			
			$tag->setAttribute('src', $src);

			$align = $imageData->getAlign();
			if ( ! empty($align)) {
				$tag->addClass('align-' . $align);
			}

			$tag->addClass($imageData->getStyle());

			if ( ! empty($width)) {
				$tag->setAttribute('width', $width);
			}

			if ( ! empty($height)) {
				$tag->setAttribute('height', $height);
			}

			$title = trim($imageData->getTitle());
			if ( ! empty($title)) {
				$tag->setAttribute('title', $title);
			}
			
			$alternativeText = trim($imageData->getAlternativeText());
			$tag->setAttribute('alt', ( ! empty($alternativeText) ? $alternativeText : ''));
			
			$html = $tag->toHtml();
		}

		return $html;
	}

	/**
	 * Parse supra.video
	 * @param \Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement $videoData
	 * @return string
	 */
	protected function parseSupraVideo(VideoReferencedElement $element)
	{
		$html = null;
		
		$resource = $element->getResource();
			
		if ($resource == VideoReferencedElement::RESOURCE_LINK) {
			
			$service = $element->getExternalService();
			$width = 560;
			$height = 315;
			
			$videoId = $element->getExternalId();
			
			$wmodeParam = null;
			if ($this->requestType == self::REQUEST_TYPE_EDIT) {
				$wmodeParam = 'wmode="opaque"';
			}
			
			if ($service == VideoReferencedElement::SERVICE_YOUTUBE) {
				$html = "<div class=\"video\" data-attach=\"$.fn.resize\">
				<object width=\"{$width}\" height=\"{$height}\">
					<param name=\"movie\" value=\"http://www.youtube.com/v/{$videoId}?hl=en_US&amp;version=3&amp;rel=0\"></param>
					<param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param>
					
					<embed {$wmodeParam} src=\"http://www.youtube.com/v/{$videoId}?hl=en_US&amp;version=3&amp;rel=0\" type=\"application/x-shockwave-flash\" width=\"{$width}\" height=\"{$height}\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>
				</object>
			</div>";		
			}
			else if ($service == VideoReferencedElement::SERVICE_VIMEO) {
				$html = "<div class=\"video\" data-attach=\"$.fn.resize\">
				<iframe src=\"http://player.vimeo.com/video/{$videoId}?title=0&amp;byline=0&amp;portrait=0&amp;color=0&amp;api=1&amp;player_id=player{$videoId}\" id=\"player{$videoId}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
				</div>";
			}
		}
		else if ($resource == VideoReferencedElement::RESOURCE_SOURCE) {
			
			$width = $element->getWidth();
			$height = $element->getHeight();
			$src = $element->getExternalPath();
			
			if ($element->getExternalSourceType() == VideoReferencedElement::SOURCE_IFRAME) {
				$html = "<div class=\"video\" data-attach=\"$.fn.resize\">
					<iframe src=\"{$src}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
					</div>";
			}			
			else if ($element->getExternalSourceType() == VideoReferencedElement::SOURCE_EMBED) {
				
				$wmodeParam = null;
				if ($this->requestType == self::REQUEST_TYPE_EDIT) {
					$wmodeParam = 'wmode="opaque"';
				}
				
				$html = "<div class=\"video\" data-attach=\"$.fn.resize\">
					<object width=\"{$width}\" height=\"{$height}\">
					<param name=\"movie\" value=\"{$src}\"></param>
					<param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param>
					<embed {$wmodeParam} src=\"{$src}\" type=\"application/x-shockwave-flash\" width=\"{$width}\" height=\"{$height}\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>
				</object></div>";
			}
		}
	
		return $html;
	}

	/**
	 * @param string $content
	 * @return Twig_Markup
	 */
	public function filter($content)
	{
		$metadata = $this->property->getMetadata();
		$metaItem = $metadata->get(0);
		
		if ( ! $metaItem instanceof Entity\BlockPropertyMetadata) {
			return null;
		}
		
		$element = $metaItem->getReferencedElement();
		
		if ($element instanceof Entity\ReferencedElement\ReferencedElementAbstract) {
			
			if ($element instanceof VideoReferencedElement) {
				$filteredValue = $this->parseSupraVideo($element);
			}
			else if ($element instanceof Entity\ReferencedElement\ImageReferencedElement) {
				$filteredValue = $this->parseSupraImage($element);
			}
		
			$markup = new Twig_Markup($filteredValue, 'UTF-8');

			return $markup;
		}
	}		
}
