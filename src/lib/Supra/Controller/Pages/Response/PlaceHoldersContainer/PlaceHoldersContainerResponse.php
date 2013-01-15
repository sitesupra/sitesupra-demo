<?php

namespace Supra\Controller\Pages\Response\PlaceHoldersContainer;

use Supra\Response\HttpResponse;

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
	public function addPlaceHolderResponse(PlaceHolderResponse $placeHolderResponse)
	{
		$placeHolder = $placeHolderResponse->getPlaceHolder();
		$placeHolderName = $placeHolder->getName();
		
		if (is_null($this->container)) {
			$this->container = $placeHolder->getContainer();
			$this->group = $placeHolder->getPlaceholderSetName();
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
	
}
