<?php

namespace Supra\Package\Cms\Entity\ReferencedElement;

use Supra\ObjectRepository\ObjectRepository;

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
	 * @var string
	 */
	protected $thumbnail;

		
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
	 * Thumbnail getter
	 * @return string | null
	 */
	public function getThumbnailUrl()
	{
		// @FIXME: functionality from supraportal
		return null;
		
		if ($this->thumbnail === null) {
			
			$this->thumbnail = false;
			
			if ($this->resource === self::RESOURCE_LINK
					&& ! empty($this->externalId)) {
				
				$cachedVersion = $this->getThumbnailPathCachedVersion($this->externalId);
				if ($cachedVersion !== false) {
					
					$this->thumbnail = $cachedVersion;
					
					return $this->thumbnail;
				}
				
				if ($this->externalService === self::SERVICE_YOUTUBE) {
					$this->thumbnail = $this->getThumbnailForYoutube($this->externalId);
					
				} else if ($this->externalService === self::SERVICE_VIMEO) {
					$this->thumbnail = $this->getThumbnailForVimeo($this->externalId);
				}
				
				$this->storeThumbnailPath($this->externalId, $this->thumbnail);
			}
		}
		
		return $this->thumbnail;
	}
	
	/**
	 * 
	 * @param string $id
	 * @return string
	 */
	private function getThumbnailForYoutube($id)
	{
		if (empty($id)) {
			return null;
		}
				
		$apiKey = ObjectRepository::getIniConfigurationLoader($this)
				->getValue('google_youtube', 'api_key', null);
		
		if (empty($apiKey)) {
			return null;
		}
		
		$query = http_build_query(array(
			'id' => $id,
			'part' => 'snippet',
			'key' => $apiKey,
		));
		
		$url = 'https://www.googleapis.com/youtube/v3/videos?' . $query; 
		
		$response = file_get_contents($url);
		if ( ! empty($response)) {
			$jsonData = json_decode($response, true);
			if ( ! empty($jsonData) && is_array($jsonData)) {
				if (isset($jsonData['items']) && ! empty($jsonData['items'])) {
					
					$itemData = array_pop($jsonData['items']);
					return $itemData['snippet']['thumbnails']['default']['url'];
				}		
			}
		}
		
		return null;
	}
	
	/**
	 * 
	 * @param string $videoId
	 * @return string
	 */
	private function getThumbnailForVimeo($id)
	{
		if (empty($id)) {
			return null;
		}
		
		$url = "http://vimeo.com/api/v2/video/{$id}.php";
		$response = file_get_contents($url);
		
		if ( ! empty($response)) {
			$data = unserialize($response);
			if (is_array($data) && ! empty($data)) {
				$videoData = array_pop($data);
				return $videoData['thumbnail_small'];
			}
		}
		
		return null;
	}
	
	/**
	 * @param string $videoId
	 * @return string | bool
	 */
	private function getThumbnailPathCachedVersion($videoId)
	{
		return ObjectRepository::getCacheAdapter('#global')
				->fetch(__NAMESPACE__ . $videoId);
	}
	
	/**
	 */
	private function storeThumbnailPath($videoId, $path)
	{
		ObjectRepository::getCacheAdapter('#global')
				->save(__NAMESPACE__ . $videoId, $path);
	}
}
