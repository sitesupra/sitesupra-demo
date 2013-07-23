<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

/**
 * @Entity
 */
class VideoReferencedElement extends ReferencedElementAbstract
{

	const TYPE_ID = 'video';
	
	const RESOURCE_SOURCE = 'source';
	const RESOURCE_LINK	= 'link';
	const RESOURCE_FILE	= 'file';
	
	const SOURCE_EMBED = 'embed';
	const SOURCE_IFRAME = 'iframe';
	
	const SERVICE_YOUTUBE = 'youtube';
	const SERVICE_VIMEO = 'vimeo';
	
	/**
	 * MediaLibrary file Id
	 * 
	 * @Column(type="supraId20", nullable=true)
	 * @var string
	 */
	protected $fileId;
		
	/**
	 * Type of video resource - link, local file or iframe/embed
	 * 
	 * @Column(type="string")
	 * @var string
	 */
	protected $resource;
		
	/**
	 * Vimeo/Youtube id
	 * 
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $externalId;
	
	/**
	 * 
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $externalSourceType;
	
	/**
	 * 
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $externalSource;
		
	/**
	 * Embed/Iframe video path
	 * 
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $externalPath;
		
	/**
	 * 
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $externalService;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $width;

	/**
	 * @Column(type="integer", nullable=true)
	 * @var integer
	 */
	protected $height;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $align;

		
	/**
	 * @return string
	 */
	public function getResource()
	{
		return $this->resource;
	}
	
	/**
	 * @param string $resource
	 * @throws \InvalidArgumentException
	 */
	public function setResource($resource)
	{
		$allowedTypes = array(
			self::RESOURCE_SOURCE,
			self::RESOURCE_LINK,
			self::RESOURCE_FILE,
		);
		
		if ( ! in_array($resource, $allowedTypes)) {
			throw new \InvalidArgumentException("Invalid resource type {$resource} received");
		}
		
		$this->resource = $resource;
	}
	
	/**
	 * @param string $source
	 */
	public function setExternalSource($source)
	{
		$this->externalSource = $source;
	}
	
	/**
	 * @return string
	 */
	public function getExternalSource()
	{
		return $this->externalSource;
	}
	
	/**
	 * @param string $sourceType
	 */
	public function setExternalSourceType($sourceType)
	{
		$this->externalSourceType = $sourceType;
	}
	
	/**
	 * @return string
	 */
	public function getExternalSourceType()
	{
		return $this->externalSourceType;
	}
	
	/**
	 * @return string
	 */
	public function getFileId()
	{
		return $this->fileId;
	}
	
	/**
	 * @param string $fileId
	 */
	public function setFileId($fileId)
	{
		$this->fileId = $fileId;
	}
	
	/**
	 * @return string
	 */
	public function getExternalId()
	{
		return $this->externalId;
	}
	
	/**
	 * @param string $videoId
	 */
	public function setExternalId($videoId)
	{
		$this->externalId = $videoId;
	}
	
	/**
	 * @return string
	 */
	public function getExternalService()
	{
		return $this->externalService;
	}
	
	/**
	 * @return string
	 */
	public function getExternalPath()
	{
		return str_replace(array('http://', 'https://'), '', $this->externalPath);
	}

	/**
	 * @param string $videoPath
	 */
	public function setExternalPath($videoPath)
	{
		$this->externalPath = $videoPath;
	}

	/**
	 * @param string $service
	 */
	public function setExternalService($service)
	{
		if ( ! is_null($service) && ! in_array($service, array(self::SERVICE_YOUTUBE, self::SERVICE_VIMEO))) {
			throw new \InvalidArgumentException("Invalid video service {$service} received");
		}
		
		$this->externalService = $service;
	}
	
	/**
	 * @return integer
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @param integer $width
	 */
	public function setWidth($width)
	{
		$width = (int) $width;

		if ($width < 0) {
			throw new \InvalidArgumentException("Negative width '$width' received");
		} elseif ($width == 0) {
			$width = null;
		}

		$this->width = $width;
	}

	/**
	 * @return integer
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * @param integer $height
	 */
	public function setHeight($height)
	{
		$height = (int) $height;

		if ($height < 0) {
			throw new \InvalidArgumentException("Negative height '$height' received");
		} elseif ($height == 0) {
			$height = null;
		}

		$this->height = $height;
	}
	
	/**
	 * @return string
	 */
	public function getAlign()
	{
		return $this->align;
	}

	/**
	 * @param string $align
	 * @throws \InvalidArgumentException
	 */
	public function setAlign($align)
	{
		if ( ! empty($align) && ! in_array($align, array('middle', 'left', 'right'))) {
			throw new \InvalidArgumentException("Unknown align value \"{$align}\" received");
		}
		
		$this->align = $align;
	}

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'type' => self::TYPE_ID,
			'resource' => $this->resource,
			'id' => $this->externalId,
			'src' => $this->externalPath,
			'service' => $this->externalService,
			'source' => $this->externalSource,
			'source_type' => $this->externalSourceType,
			'width' => $this->width,
			'height' => $this->height,
			'align' => $this->align,
		);

		return $array;
	}

	/**
	 * {@inheritdoc}
	 * @param array $array
	 */
	public function fillArray(array $array)
	{		
		$array = $array + array(
			'resource' => null,
			'src' => null,
			'id' => null,
			'service' => null,
			'width' => null,
			'height' => null,
			'source' => null,
			'source_type' => null,
			'align' => null,
		);
		
		$this->setResource($array['resource']);
		$this->setAlign($array['align']);
		$this->setWidth($array['width']);
		$this->setHeight($array['height']);
				
		if ($this->resource == self::RESOURCE_SOURCE) {

			$this->setExternalPath($array['src']);
			$this->setExternalSource($array['source']);
			$this->setExternalSourceType($array['source_type']);
			
			$this->externalService = null;
			$this->externalId = null;
		}
		else if ($this->resource == self::RESOURCE_LINK) {
			$this->setExternalId($array['id']);
			$this->setExternalService($array['service']);
			
			$this->externalPath = null;
			$this->externalSource = null;
			$this->externalSourceType = null;
		}
		else if ($this->resource == self::RESOURCE_FILE) {
			
			$this->setFileId($array['id']);
			
			$this->externalService = null;
			$this->externalId = null;
			$this->externalPath = null;
			$this->externalSource = null;
			$this->externalSourceType = null;
		}
	}
	
	/**
	 * Tries to parse raw user input coming from VideoEditable (video links from services
	 * like YouTube and Vimeo, or embed/iframe code) and responses with array of data, or
	 * false if nothing suitable was inside
	 *  
	 * @param string $inputString
	 */
	public static function parseVideoSourceInput($inputString)
	{
		$string = trim($inputString);
		
		// check for embed source code
		if (mb_stripos($string, '<embed') !== false || mb_stripos($string, '<iframe') !== false) {
						
			$string = strip_tags($string, '<iframe><object><embed><param>');
					
			libxml_use_internal_errors(true);
			$dom = new \DOMDocument();

			if ( ! $dom->loadHTML($string)) {
				return false;
			}
			
			libxml_clear_errors();
			libxml_use_internal_errors(false);
			
			$node = null;
			$externalSourceType = null;
			
			if (mb_stripos($string, 'iframe') !== false) {
				$node = $dom->getElementsByTagName('iframe')->item(0);	
				$externalSourceType = self::SOURCE_IFRAME;
			} else {
				$node = $dom->getElementsByTagName('embed')->item(0);
				$externalSourceType = self::SOURCE_EMBED;
			}
			
			if ( ! $node instanceof \DOMElement) {
				return false;	
			}
			
//			$width = (int) $node->getAttribute('width');
//			$height = (int) $node->getAttribute('height');
			$src = $node->getAttribute('src');

			// only known sources (youtube, vimeo, facebook) are allowed
			$urlMatch = array();
			if ( ! preg_match('/(?:(www|player)\.)?(?:youtu\.be\/|(youtube|vimeo|facebook)\.com)(.*)+/', $src, $urlMatch) || ! isset($urlMatch[0])) {
				return false;
			}
			
			$filteredSrc = $urlMatch[0];
			
//			if ( ! (empty($width) || empty($height) || empty($src))) {
			if ( ! empty($src)) {
				return array(
					'resource' => self::RESOURCE_SOURCE,
					'source' => $string,
					'source_type' => $externalSourceType,
//					'width' => $width,
//					'height' => $height,
					'src' => $filteredSrc
				);
			}
						
		}
		
		// check for YouTube link
		$youtubePattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
		$matches = array();
		if (preg_match($youtubePattern, $string, $matches) && isset($matches[1])) {
			return array(
				'resource' => self::RESOURCE_LINK,
				'service' => self::SERVICE_YOUTUBE,
				'id' => $matches[1],
			);
		}
		
		// check for Vimeo link
		$vimeoPattern = '/(?:https?:\/\/)(?:www\.)?vimeo.com\/(?:channels\/|groups\/[^\/]*\/videos\/|album\/\d+\/video\/|)(\d+)(?:$|\/|\?)/';
		$matches = array();
		if (preg_match($vimeoPattern, $string, $matches) && isset($matches[1])) {
			return array(
				'resource' => self::RESOURCE_LINK,
				'service' => self::SERVICE_VIMEO,
				'id' => $matches[1],
			);
		}
		
		
		return false;
	}
}
