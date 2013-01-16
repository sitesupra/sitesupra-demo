<?php

namespace Supra\Controller\Pages\Response\PlaceHoldersContainer;

use Supra\Response\HttpResponse;
use Supra\Controller\Pages\Response;

class PlaceHoldersContainerResponse extends HttpResponse
{
	/**
	 * Array of responses object for each placeholder related to this container
	 * @var array
	 */
	protected $placeholderResponses = array();

	/**
	 * @var string
	 */
	protected $container;
	
	/**
	 * @var string
	 */
	protected $group;
	
	/**
	 * @return string
	 */
	public function getContainer()
	{		
		return $this->container;
	}
	
	/**
	 * @return string
	 */
	public function getGroup()
	{
		return $this->group;
	}
	
	/**
	 * @param \Supra\Controller\Pages\Response\PlaceHolder\PlaceHolderResponse $placeHolderResponse
	 */
	public function addPlaceHolderResponse(Response\PlaceHolder\PlaceHolderResponse $placeHolderResponse)
	{
		$placeHolder = $placeHolderResponse->getPlaceHolder();
		$placeHolderName = $placeHolder->getName();
		
		if (is_null($this->container)) {
			$this->container = $placeHolder->getContainer();
			$this->group = $placeHolder->getPlaceholderSetName();
			$this->id = $this->container . mt_rand(0, 200);
		}
		
		$nameInLayout = str_replace($this->container . '_', '', $placeHolderName);
		
		$this->placeHolderResponses[$nameInLayout] = $placeHolderResponse;
	}
	
	/**
	 * @return array
	 */
	public function getPlaceHolderResponses()
	{
		return $this->placeHolderResponses;
	}
	
	public function getPlaceHolderResponse($placeName)
	{
		$nameInLayout = str_replace($this->container . '_', '', $placeName);
		
		if (isset($this->placeHolderResponses[$nameInLayout])) {
			return $this->placeHolderResponses[$nameInLayout];
		}
		
		return null;
	}
	
	public function output($output)
	{
		if ($this->container == 'footer_set') {
			1+1;
		}
		
		
		1+1;
		
		parent::output($output);
	}
	
}
