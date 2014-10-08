<?php

namespace Supra\Package\Cms\Pages\Filter;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\ReferencedElement;
use Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement;
use Supra\FileStorage\Entity\Image;


class InlineMediaMarkup extends \Twig_Markup
{
	/**
	 * @var ReferencedElement\ReferencedElementAbstract
	 */
	protected $mediaElement;
	
	/**
	 * @var InlineMediaFilter
	 */
	protected $filter;
	
	/**
	 * @var array
	 */
	protected $wrappers = array();
	
	/**
	 * @TODO: video usage not implemented
	 * @var int
	 */
	protected $forcedWidth;
	
	/**
	 * @TODO: usage not implemented
	 * @var int
	 */
	protected $forcedHeight;
	
	/**
	 * @param ReferencedElement\ReferencedElementAbstract $element
	 */
	public function __construct(ReferencedElement\ReferencedElementAbstract $element, InlineMediaFilter $filter)
	{
		$this->mediaElement = $element;
		$this->filter = $filter;
		
		$this->charset = 'UTF-8';
	}
	
	/**
	 * @return bool
	 */
	public function isVideo()
	{
		return $this->mediaElement instanceof ReferencedElement\VideoReferencedElement;
	}
	
	/**
	 * @return bool
	 */
	public function isImage()
	{
		return $this->mediaElement instanceof ReferencedElement\ImageReferencedElement;
	}

	/**
	 * @param array $wrapper
	 */
	public function addWrapper(array $wrapper)
	{
		$this->wrappers[] = $wrapper;
		return $this;
	}
	
	/**
	 * @param int $width
	 */
	public function setWidth($width) 
	{
		$this->forcedWidth = (int) $width;
		return $this;
	}
	
	public function setHeight($height)
	{
		$this->forcedHeight = (int) $height;
		return $this;
	}
	
	/**
	 * @return \Twig_Markup
	 */
	public function __toString()
	{
		$elementHtml = $this->isImage() ? $this->parseSupraImage() : $this->parseSupraVideo();
		
		foreach ($this->wrappers as $wrapper) {
			
			list($open, $close) = $wrapper;
			
			$elementHtml = $open . $elementHtml . $close;
		}
		
		$this->content = $elementHtml;
		
		return parent::__toString();
	}
	
	/**
	 * Workaround
	 * @return int
	 */
	public function count()
	{
		if (empty($this->content)) {
			return 1;
		}
		
		return parent::count();
	}
	
	/**
	 * @return string
	 */
	protected function parseSupraVideo()
	{
		$html = null;
		
		$element = $this->mediaElement;
		
		$resource = $element->getResource();
		
		$width = $element->getWidth();
		$height = $element->getHeight();
		
		$align = $element->getAlign();
		$alignCssClass = ! empty($align) ? "align-$align" : '';
		
		$wmodeParam = null;
		if ($this->filter instanceof EditableInlineMedia) {
			$wmodeParam = 'wmode="opaque"';
		}
		
		if ($resource == VideoReferencedElement::RESOURCE_LINK) {
			
			$service = $element->getExternalService();
			
			$videoId = $element->getExternalId();
			
			// @TODO: this could raise a lot of requests to API service
			// we should request the thumnails only when they're explicitly required
			$thumbnail = $element->getThumbnailUrl();
			
			if ($service == VideoReferencedElement::SERVICE_YOUTUBE) {
				$html = "<div class=\"video $alignCssClass\" data-attach=\"$.fn.resize\" data-thumbnail=\"{$thumbnail}\">
					<iframe src=\"//www.youtube.com/embed/{$videoId}?{$wmodeParam}&rel=0\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" allowfullscreen></iframe>
				</div>";
			}
			else if ($service == VideoReferencedElement::SERVICE_VIMEO) {
				$html = "<div class=\"video $alignCssClass\" data-attach=\"$.fn.resize\" data-thumbnail=\"{$thumbnail}\">
					<iframe src=\"//player.vimeo.com/video/{$videoId}?title=0&amp;byline=0&amp;portrait=0&amp;color=0&amp;api=1&amp;player_id=player{$videoId}\" id=\"player{$videoId}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
				</div>";
			}
		}
		else if ($resource == VideoReferencedElement::RESOURCE_SOURCE) {
			
			$src = $element->getExternalPath();
			
			if ($element->getExternalSourceType() == VideoReferencedElement::SOURCE_IFRAME) {
				$html = "<div class=\"video $alignCssClass\" data-attach=\"$.fn.resize\">
					<iframe src=\"//{$src}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
					</div>";
			}			
			else if ($element->getExternalSourceType() == VideoReferencedElement::SOURCE_EMBED) {
				
				$html = "<div class=\"video $alignCssClass\" data-attach=\"$.fn.resize\">
					<object width=\"{$width}\" height=\"{$height}\">
					<param name=\"movie\" value=\"//{$src}\"></param>
					<param name=\"allowFullScreen\" value=\"true\"></param><param name=\"allowscriptaccess\" value=\"always\"></param>
					<embed {$wmodeParam} src=\"//{$src}\" type=\"application/x-shockwave-flash\" width=\"{$width}\" height=\"{$height}\" allowscriptaccess=\"always\" allowfullscreen=\"true\"></embed>
				</object></div>";
			}
		}
	
		return $html;
	}
	
	
	/**
	 * Parse supra.image
	 * @param ReferencedElement\ImageReferencedElement $imageData
	 * @return string
	 */
	protected function parseSupraImage()
	{
		$imageData = $this->mediaElement;
		
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
			
			if ($this->forcedWidth && $this->forcedHeight) {
				$croppedImageVariantSize = $fs->createCroppedImageVariant($size, $this->forcedWidth, $this->forcedHeight, true);
				$size = $image->findImageSize($croppedImageVariantSize);
				$src = $fs->getWebPath($image, $size);
			} else {
				$src = $fs->getWebPath($image, $size);
			}
			
			$tag->setAttribute('src', $src);

			$align = $imageData->getAlign();
			if ( ! empty($align)) {
				$tag->addClass('align-' . $align);
			}

			$tag->addClass($imageData->getStyle());

			if ($this->forcedWidth !== null) {
				$tag->setAttribute('width', $this->forcedWidth);
			} else {
				
				if ( ! empty($width)) {
					$tag->setAttribute('width', $width);
				}

				if ( ! empty($height)) {
					$tag->setAttribute('height', $height);
				}
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
}
