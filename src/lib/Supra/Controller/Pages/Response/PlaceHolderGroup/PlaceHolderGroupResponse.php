<?php

namespace Supra\Controller\Pages\Response\PlaceHolderGroup;

use Supra\Response\HttpResponse;
use Supra\Controller\Pages\Response;

class PlaceHolderGroupResponse extends HttpResponse
{
	/**
	 * Array of responses object for each placeholder related to this group
	 * @var array
	 */
	protected $placeholderResponses = array();

	/**
	 * @var string
	 */
	protected $groupName;
	
	/**
	 * @var string
	 */
	protected $groupLayout;
	
	/**
	 * @return string
	 */
	public function getGroupName()
	{		
		return $this->groupName;
	}
	
	/**
	 * @param string $name
	 */
	public function setGroupName($name)
	{
		$this->groupName = $name;
	}
	
	/**
	 * @return type
	 */
	public function getGroupLayout()
	{
		return $this->groupLayout;
	}
	
	/**
	 * @param type $layout
	 */
	public function setGroupLayout($layout)
	{
		$this->groupLayout = $layout;
	}
	
	/**
	 * @param \Supra\Controller\Pages\Response\PlaceHolder\PlaceHolderResponse $placeHolderResponse
	 */
	public function addPlaceHolderResponse(Response\PlaceHolder\PlaceHolderResponse $placeHolderResponse)
	{
		$placeHolder = $placeHolderResponse->getPlaceHolder();
		$placeHolderName = $placeHolder->getName();
		
		$nameInLayout = str_replace($this->groupName . '_', '', $placeHolderName);
		
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
		$nameInLayout = str_replace($this->groupName . '_', '', $placeName);
		
		if (isset($this->placeHolderResponses[$nameInLayout])) {
			return $this->placeHolderResponses[$nameInLayout];
		}
		
		return null;
	}
		
}
