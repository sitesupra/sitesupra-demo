<?php

namespace Supra\Controller\Pages\Validator;

use Supra\Validator\Exception\ValidationFailure;
use Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement;

/**
 * Embed video data type validator
 */
class EmbedVideo implements \Supra\Validator\Type\ValidationTypeInterface
{
	/**
	 * Source code types
	 * IFRAME - code within the iframe tag
	 * EMBED - code within the embed tag
	 * LINK - the link to video service (YouTube, Vimeo)
	 */
	const SOURCE_TYPE_IFRAME = 'iframe';
	const SOURCE_TYPE_EMBED = 'embed';
	const SOURCE_TYPE_LINK = 'link';
	
	/**
	 * Known, and supported services are
	 * Vimeo, Youtube, Facebook
	 */
	const SERVICE_VIMEO = 'vimeo';
	const SERVICE_YOUTUBE = 'youtube';
	const SERVICE_FACEBOOK = 'facebook';
	
	/**
	 * Source validation patterns
	 */
	const PATTERN_EMBED_CODE = '/(?:(www|player)\.)?(?:youtu\.be\/|(youtube|vimeo|facebook)\.com)(.*)+/';
	const PATTERN_LINK_VIMEO = '/(?:https?:\/\/)(?:www\.)?vimeo.com\/(?:channels\/|groups\/[^\/]*\/videos\/|album\/\d+\/video\/|)(\d+)(?:$|\/|\?)/';
	const PATTERN_LINK_YOUTUBE = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
	
	const PATTERN_ID_YOUTUBE = '%([^"&?/ ]{11})%i';
	const PATTERN_ID_VIMEO = '/(\d+)/';
	
	
	/**
	 * {@inheritDoc}
	 */
	public function isValid($value, $additionalParameters = null)
	{
		try {
			$this->validate($value, $additionalParameters);
		} catch (ValidationFailure $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function sanitize(&$value, $additionalParameters = null)
	{
		try {
			$this->validate($value, $additionalParameters);
		} catch (ValidationFailure $e) {
			$value = null;
			return false;
		}
		
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function validate(&$value)
	{
		$value = $value + array('type' => null, 'resource' => null, 'id' => null,
			'src' => null, 'service' => null, 'source' => null, 'source_type' => null,
			'width' => null, 'height' => null, 'align' => null,
		);
		
		if ($value['width'] <= 0 && $value['height'] <= 0) {
			throw new ValidationFailure('Dimensions are invalid');
		}
		
		if ( ! empty($value['source'])) {
			$videoData = $this->getVideoDataFromSource($value['source']);
			if ($videoData === false) {
				throw new ValidationFailure('Failed to parse video source');
			}
			
			$resource = null;
			switch ($videoData['type']) {
				case self::SOURCE_TYPE_EMBED:
				case self::SOURCE_TYPE_IFRAME:
					$resource = VideoReferencedElement::RESOURCE_SOURCE;
					break;
				case self::SOURCE_TYPE_LINK:
					$resource = VideoReferencedElement::RESOURCE_LINK;
					break;
			}
			
			$value['resource'] = $resource;
			$value['source_type'] = $videoData['type'];
			$value['service'] = $videoData['service'];
			$value['src'] = $videoData['video_src'];
			$value['id'] = $videoData['video_id'];
			$value['source'] = $videoData['source'];
			
			return true;

		} else if ($value['resource'] === self::SOURCE_TYPE_LINK
				&& ! empty($value['id'])) {
						
			if ($value['service'] === self::SERVICE_YOUTUBE
					&& preg_match(self::PATTERN_ID_YOUTUBE, $value['id'])) {
				
				return true;
			}
			else if ($value['service'] === self::SERVICE_VIMEO
					&& preg_match(self::PATTERN_ID_VIMEO, $value['id'])) {
				
				return true;				
			}
			
			throw new ValidationFailure("Provided video ID seems to be invalid");
		}
		
		throw new ValidationFailure('Data array is incomplete');
	}
	
	/**
	 * Tries to parse the provided source string 
	 * and returns the video data array or false if parse failed
	 * 
	 * @param string $source
	 * @return array | boolean
	 */
	private function getVideoDataFromSource($source)
	{
		$string = trim($source);
		
		// check for embed source code
		if (mb_stripos($string, '<embed') !== false 
				|| mb_stripos($string, '<iframe') !== false) {
						
			$string = strip_tags($string, '<iframe><object><embed><param>');
					
			libxml_use_internal_errors(true);
			$dom = new \DOMDocument();

			if ( ! $dom->loadHTML($string)) {
				return false;
			}
			
			libxml_clear_errors();
			libxml_use_internal_errors(false);
			
			$node = null;
			$type = null;
			
			if (mb_stripos($string, 'iframe') !== false) {
				$node = $dom->getElementsByTagName('iframe')->item(0);	
				$type = self::SOURCE_TYPE_IFRAME;
			} else {
				$node = $dom->getElementsByTagName('embed')->item(0);
				$type = self::SOURCE_TYPE_EMBED;
			}
			
			if ( ! $node instanceof \DOMElement) {
				return false;	
			}
			
			$src = $node->getAttribute('src');

			// only known sources (youtube, vimeo, facebook) are allowed
			$urlMatch = array();
			if ( ! preg_match(self::PATTERN_EMBED_CODE, $src, $urlMatch) || ! isset($urlMatch[0])) {
				return false;
			}
			
			$filteredSrc = $urlMatch[0];
			
			if ( ! empty($filteredSrc)) {
				return array(
					'type' => $type,
					'service' => null,	//$service, // fixme
					'video_id' => null,
					'video_src' => $filteredSrc,
					'source' => $string,
				);
			}			
		}
		
		// check for YouTube link
		$matches = array();
		if (preg_match(self::PATTERN_LINK_YOUTUBE, $string, $matches) && isset($matches[1])) {
			return array(
				'type' => self::SOURCE_TYPE_LINK,
				'service' => self::SERVICE_YOUTUBE,
				'video_id' => $matches[1],
				'video_src' => null,
				'source' => $string,
			);
		}
		
		// check for Vimeo link
		$matches = array();
		if (preg_match(self::PATTERN_LINK_VIMEO, $string, $matches) && isset($matches[1])) {
			return array(
				'type' => self::SOURCE_TYPE_LINK,
				'service' => self::SERVICE_VIMEO,
				'video_id' => $matches[1],
				'video_src' => null,
				'source' => $string,
			);
		}
		
		return false;
	}
}