<?php

namespace Supra\Package\Cms\Pages\Set;

use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Exception;

/**
 * Set of place holders
 */
class PlaceHolderSet extends AbstractSet
{
	/**
	 * @var Entity\Abstraction\Localization
	 */
	private $localization;
	
	/**
	 * @var PlaceHolderSet 
	 */
	private $finalPlaceHolderSet;
	
	/**
	 * @var PlaceHolderSet
	 */
	private $parentPlaceHolderSet;

	/**
	 * @param Localization $localization
	 */
	public function __construct(Localization $localization = null)
	{
		$this->localization = $localization;
		
		if (isset($localization)) {
			$this->finalPlaceHolderSet = new PlaceHolderSet();
			$this->parentPlaceHolderSet = new PlaceHolderSet();
		}
	}
	
	/**
	 * Use only append to fill this object, must start with top elements
	 * @param Entity\Abstraction\PlaceHolder $placeHolder
	 */
	public function append($placeHolder)
	{
		if ( ! $placeHolder instanceof PlaceHolder) {
			throw new Exception\LogicException(__METHOD__ . " accepts PlaceHolder arguments only");
		}
		
		if (isset($this->localization)) {
			
			$placeHolderName = $placeHolder->getName();
			
			// Add to final in cases when it's the page place or locked one
			if ($placeHolder->getLocked() || $placeHolder->getMaster()->equals($this->localization)) {
				
				// Ignore if already set locked place with higher hierarchy level
				if ($this->finalPlaceHolderSet->offsetExists($placeHolderName)) {
					return;
				}
				
				$this->finalPlaceHolderSet->offsetSet($placeHolderName, $placeHolder);
			} else {
				
				// collect not matched template place holders to search for locked blocks
				$this->parentPlaceHolderSet->append($placeHolder);
			}
		}
		
		parent::append($placeHolder);
	}
	
	/**
	 * The list of final (locked or belongs to the final master) placeHolders.
	 * The block list will be taken from these placeHolders.
	 * @return PlaceHolderSet
	 */
	public function getFinalPlaceHolders()
	{
		return $this->finalPlaceHolderSet;
	}
	
	/**
	 * The list of placeHolders which are parents of final placeHolders.
	 * The locked blocks will be searched within these placeHolders.
	 * @return PlaceHolderSet
	 */
	public function getParentPlaceHolders()
	{
		return $this->parentPlaceHolderSet;
	}
	
	/**
	 * Loads the last place holder in the set by the name provided
	 * @param string $name
	 * @return Entity\Abstraction\PlaceHolder
	 */
	public function getLastByName($name)
	{
		$placeHolder = null;
		
		/* @var $placeHolder Entity\Abstraction\PlaceHolder */
		foreach ($this as $placeHolderTest) {
			if ($placeHolderTest->getName() == $name) {
				$placeHolder = $placeHolderTest;
			}
		}
		
		return $placeHolder;
	}
}
