<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

/**
 * @Entity
 */
class VideoReferencedElement extends ReferencedElementAbstract
{

	const TYPE_ID = 'video';
	
	const RESOURCE_SOURCE = 'source';
	const RESOURCE_LINK = 'link';
	
	const SERVICE_YOUTUBE = 'youtube';
	const SERVICE_VIMEO = 'vimeo';

	/**
	 * MediaLibrary file Id
	 * @Column(type="supraId20", nullable=true)
	 * @var string
	 */
	protected $fileId;
	
	/**
	 * Vimeo/Youtube id
	 * 
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $externalId;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $resource;
		
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $service;

	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $embedCode;

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
	 * @return string
	 */
	public function getFileId()
	{
		return $this->fileId;
	}
	
	/**
	 * @param string $videoId
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
	 * @param string $externalId
	 */
	public function setExternalId($externalId)
	{
		$this->externalId = $externalId;
	}
	
	/**
	 * @return string
	 */
	public function getExternalService()
	{
		return $this->service;
	}
	
	/**
	 * @param string $service
	 */
	public function setExternalService($service)
	{
		if ( ! is_null($service) && ! in_array($service, array(self::SERVICE_YOUTUBE, self::SERVICE_VIMEO))) {
			throw new \InvalidArgumentException("Invalid video service {$service} received");
		}
		
		$this->service = $service;
	}
	
	/**
	 * @return string
	 */
	public function getResourceType()
	{
		return $this->resource;
	}
	
	/**
	 * @param string $resourceType
	 * @throws \InvalidArgumentException
	 */
	public function setResourceType($resourceType)
	{
		if ( ! in_array($resourceType, array(self::RESOURCE_LINK, self::RESOURCE_SOURCE))) {
			throw new \InvalidArgumentException("Invalid resource type {$resourceType} received");
		}
		
		$this->resource = $resourceType;
	}
	
	/**
	 * @return string
	 */
	public function getEmbedCode()
	{
		return $this->embedCode;
	}
	
	/**
	 * @param string $code
	 */
	public function setEmbedCode($code)
	{
		$this->embedCode = $code;
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
	 * {@inheritdoc}
	 * @return array
	 */
	public function toArray()
	{
		$array = array(
			'type' => self::TYPE_ID,
			'resource' => $this->resource,
//			'id' => ($this->resource == self::RESOURCE_LINK ? $this->externalId : $this->fileId),
			'id' => $this->externalId,
			'service' => $this->service,
			'file_id' => $this->fileId,
			'source' => $this->embedCode,
			'width' => $this->width,
			'height' => $this->height,
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
			'id' => null,
			'file_id' => null,
			'resource' => null,
			'service' => null,
			'source' => null,
			'width' => null,
			'height' => null,
		);

		$this->externalId = $array['id'];
		$this->fileId = $array['file_id'];
		
		$this->setResourceType($array['resource']);
		$this->setExternalService($array['service']);
		$this->setEmbedCode($array['source']);
		$this->setWidth($array['width']);
		$this->setHeight($array['height']);
		
		$this->parseSourceString($array['source']);
	}
	
	public function parseSourceString($sourceString)
	{
		if (empty($sourceString)) {
			return;
		}	
		
		$sourceString = trim($sourceString);
		

		$resourceType = null;
		
		$embedCode = null;
		
		$service = null;
		$videoId = null;
		
		// is it an url?
		$parsedUrl = parse_url($sourceString);
		if ( ! (is_array($parsedUrl) && isset($parsedUrl['host']))) {
			// no, it's not
			
			// is it some embed code?
			if (strpos(mb_strtolower($sourceString), 'embed') !== false) {
				
				$resourceType = self::RESOURCE_SOURCE;
				$embedCode = strip_tags($sourceString, '<iframe><object><embed><param>');
			} else {
				// not an embed code, invalid argument specified
				throw new \InvalidArgumentException("Invalid video source {$sourceString} provided");
			}
		} else {
			
			$resourceType = self::RESOURCE_LINK;
			
			if ( ! isset($parsedUrl['host'])) {
				throw new \InvalidArgumentException("Failed to properly parse provided video link {$sourceString}");
			}
			
			$hostName = $parsedUrl['host'];
			$hostName = mb_strtolower($hostName);
			
			$videoId = null;
			
			switch($hostName) {
				case 'youtu.be':
				case 'youtube.com':
				case 'www.youtube.com':
				case 'www.youtu.be':
					$service = self::SERVICE_YOUTUBE;
					preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $sourceString, $match);
					
					$videoId = $match[1];
					break;
				case 'vimeo.com':
					$service = self::SERVICE_VIMEO;
					$videoId = $parsedUrl['path'];
				default:
					throw new \InvalidArgumentException("Unrecognized video service hostname {$hostName}");
			}
		}
		
		$this->externalId = $videoId;
		$this->embedCode = $embedCode;
		$this->service = $service;
		$this->resource = $resourceType;
	}
		
}
