<?php

namespace Supra\Controller\Pages\Response\PlaceHolder;

use Symfony\Component\HttpFoundation\Response;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;

abstract class PlaceHolderResponse extends Response
{
	/**
	 * @var PlaceHolder
	 */
	private $placeHolder;
	
	/**
	 * @return PlaceHolder
	 */
	public function getPlaceHolder()
	{
		return $this->placeHolder;
	}

	/**
	 * @param PlaceHolder $placeHolder
	 */
	public function setPlaceHolder(PlaceHolder $placeHolder)
	{
		$this->placeHolder = $placeHolder;
	}

}
