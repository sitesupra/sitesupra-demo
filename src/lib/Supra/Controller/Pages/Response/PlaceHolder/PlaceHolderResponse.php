<?php

namespace Supra\Controller\Pages\Response\PlaceHolder;

use Supra\Response\HttpResponse;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Editable\EditableAbstraction;
use Supra\Controller\Pages\Entity\Abstraction\PlaceHolder;

/**
 * Response for place holder
 */
abstract class PlaceHolderResponse extends HttpResponse
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
