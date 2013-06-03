<?php

namespace Supra\Controller\Pages\Filter;

use Supra\Editable\Filter\FilterInterface;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Log\Writer\WriterAbstraction;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Markup;
use Supra\FileStorage\Entity\Image;
use Twig_Markup;
use Supra\Response\ResponseContext;

/**
 * Parses supra markup tags inside the HTML content
 */
class ParsedHtmlFilter implements FilterInterface
{

	const REQUEST_TYPE_VIEW = 0;
	const REQUEST_TYPE_EDIT = 1;
	
	/**
	 * @var BlockProperty
	 */
	public $property;

	/**
	 * @var WriterAbstraction
	 */
	private $log;

	/**
	 * Response context object
	 * Used to pass somehow the list of HTML fonts to templates
	 * 
	 * @var \Supra\Response\ResponseContext
	 */
	protected $responseContext;
	
	/**
	 * @var int
	 */
	protected $requestType;
	
	
	/**
	 * Create log instance
	 */
	public function __construct()
	{
		$this->log = ObjectRepository::getLogger($this);
	}
	
	/**
	 * @param \Supra\Response\ResponseContext $context
	 */
	public function setResponseContext(ResponseContext $context)
	{
		$this->responseContext = $context;
	}
	
	/**
	 * Parse supra.link, return beginning part of referenced link element.
	 * @param Entity\ReferencedElement\LinkReferencedElement $link
	 * @return string
	 */
	private function parseSupraLinkStart(Entity\ReferencedElement\LinkReferencedElement $link)
	{
		$attributes = array(
				'target' => $link->getTarget(),
				'title' => $link->getTitle(),
				'href' => $link->getUrl(),
				'class' => $link->getClassName(),
		);  

		$tag = new \Supra\Html\HtmlTagStart('a');

		foreach ($attributes as $attributeName => $attributeValue) {

			if ($attributeValue != '') {
				$tag->setAttribute($attributeName, $attributeValue);
			}
		}
		
		if ($link->getResource() == Entity\ReferencedElement\LinkReferencedElement::RESOURCE_FILE) {
			
			$tag->setAttribute('target', '_blank');
			
			$file = $link->getFile();
		
			if ( ! empty($file)) {
				$extension = $file->getExtension();
				$tag->addClass('file-' . mb_strtolower($extension));

				$modificationTime = $file->getModificationTime();
				
				if ( ! empty($modificationTime)) {
					$tag->setAttribute('data-modification-date', $modificationTime->format('d'));
					$tag->setAttribute('data-modification-month', $modificationTime->format('m'));
					$tag->setAttribute('data-modification-year', $modificationTime->format('Y'));
				}
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
		$image = $em->find(Image::CN(), $imageId);
				
		if ( ! $image instanceof Image) {
			$this->log->warn("Image #{$imageId} has not been found");
		}
		else {
			$sizeName = $imageData->getSizeName();
			$size = $image->findImageSize($sizeName);
			
			if ( ! $size) {
				$this->log->warn("Image #{$imageId} size $sizeName has not been found");
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
			if ($this->requestType == self::REQUEST_TYPE_EDIT) {
				$fileExists = $fs->fileExists($image);
				
				$tag->setAttribute('data-exists', $fileExists);
				
				if ( ! $fileExists) {
					$src =  \Supra\FileStorage\FileStorage::MISSING_IMAGE_PATH;
					$width = $height = null;
				}
			}
			
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
	 * Parse supra.icon
	 */
	private function parseSupraIcon(Entity\ReferencedElement\IconReferencedElement $iconData)
	{
		$html = null;
		
		$iconId = $iconData->getIconId();
		
		// @FIXME: handle with FileStorage instead of Theme
		//		or with something else?
		$themeConfiguration = ObjectRepository::getThemeProvider($this)
				->getCurrentTheme()
				->getConfiguration();
		
		$iconConfiguration = $themeConfiguration->getIconConfiguration();
		
		if ( ! $iconConfiguration instanceof \Supra\Controller\Layout\Theme\Configuration\ThemeIconSetConfiguration) {
			$this->log->warn("No icons configuration object found");
			return null;
		}
		
		$svgContent = $iconConfiguration->getIconSvgContent($iconId);
		
		if ( ! empty($svgContent)) {
			
			$tag = new \Supra\Html\HtmlTag('svg');
			$style = '';
			
			$tag->setContent($svgContent);
			
			$tag->setAttribute('version', '1.1');
			$tag->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
			$tag->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
			$tag->setAttribute('x', '0px');
			$tag->setAttribute('y', '0px');
			$tag->setAttribute('viewBox', '0 0 512 512', true);
			$tag->setAttribute('enable-background', 'new 0 0 512 512');
			$tag->setAttribute('xml:space', 'preserve');

			$color = $iconData->getColor();
			if ( ! empty($color)) {
				$style = "fill: {$color};";
			}

			$align = $iconData->getAlign();
			if ( ! empty($align)) {
				$tag->addClass('align-' . $align);
			}

			$width = $iconData->getWidth();
			if ( ! empty($width)) {
				$tag->setAttribute('width', $width);
                $style .= "width: {$width}px;";
			}

			$height = $iconData->getHeight();
			if ( ! empty($height)) {
				$tag->setAttribute('height', $height);
                $style .= "height: {$height}px;";
			}
                        
            if ( ! empty($style)) {
                $tag->setAttribute('style', $style);
            }
			
			$html = $tag->toHtml();
		}

		return $html;
	}
	
	
	/**
	 * Replace image/link supra tags with real elements
	 * @param string $value
	 * @param array $metadataElements
	 * @return string 
	 */
	protected function parseSupraMarkup($value, array $metadataElements = array())
	{
		$tokenizer = new Markup\DefaultTokenizer($value);

		$tokenizer->tokenize();

		$result = array();

		foreach ($tokenizer->getElements() as $element) {

			if ($element instanceof Markup\HtmlElement) {
				$result[] = $element->getContent();
			}
			else if ($element instanceof Markup\SupraMarkupImage) {

				if ( ! isset($metadataElements[$element->getId()])) {
					$this->log->warn("Referenced image element " . get_class($element) . "-" . $element->getId() . " not found for {$this->property}");
				}
				else {

					$image = $metadataElements[$element->getId()];
					$result[] = $this->parseSupraImage($image);
				}
			}
			
			else if ($element instanceof Markup\SupraMarkupIcon) {
				if ( ! isset($metadataElements[$element->getId()])) {
					$this->log->warn("Referenced icon element " . get_class($element) . "-" . $element->getId() . " not found for {$this->property}");
				}
				else {

					$icon = $metadataElements[$element->getId()];
					$result[] = $this->parseSupraIcon($icon);
				}
			}
			
			else if ($element instanceof Markup\SupraMarkupVideo) {

				if ( ! isset($metadataElements[$element->getId()])) {
					$this->log->warn("Referenced video element " . get_class($element) . "-" . $element->getId() . " not found for {$this->property}");
				}
				else {

					$video = $metadataElements[$element->getId()];
					$result[] = $this->parseSupraVideo($video);
				}
			}
			else if ($element instanceof Markup\SupraMarkupLinkStart) {

				if ( ! isset($metadataElements[$element->getId()])) {
					$this->log->warn("Referenced link element " . get_class($element) . "-" . $element->getId() . " not found for {$this->property}");
				}
				else {

					$link = $metadataElements[$element->getId()];
					// Overwriting in case of duplicate markup tag usage
					ObjectRepository::setCallerParent($link, $this, true);
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
	 * Parse supra.video
	 * @param \Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement $videoData
	 * @return string
	 */
	private function parseSupraVideo(VideoReferencedElement $element)
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
//		$value = $this->property->getValue();
		$metadata = $this->property->getMetadata();
		
		$this->registerUsedFonts($content['fonts']);
		
		$elements = array();
		foreach ($metadata as $key => $metadataItem) {
			$elements[$key] = $metadataItem->getReferencedElement();
		}
				
		return $this->doFilter($content['html'], $elements);
	}

	/**
	 * @param string $html
	 * @param array $referencedElements
	 * @return \Twig_Markup
	 */
	public function doFilter($html, $referencedElements)
	{
		$filteredValue = $this->parseSupraMarkup($html, $referencedElements);
		$markup = new Twig_Markup($filteredValue, 'UTF-8');
		
		return $markup;
	}
	
	protected function registerUsedFonts($fontFamilies)
	{
		if ($this->responseContext instanceof ResponseContext) {
			$this->responseContext->registerGoogleFontFamilies($fontFamilies);
		}
	}
		
}
