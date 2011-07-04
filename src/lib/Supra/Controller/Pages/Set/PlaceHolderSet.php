<?php

namespace Supra\Controller\Pages\Set;

use Supra\Controller\Pages\Entity,
		Supra\Controller\Pages\Exception;

/**
 * Set of place holders
 */
class PlaceHolderSet extends AbstractSet
{
	/**
	 * @var Entity\Abstraction\Page
	 */
	private $page;
	
	/**
	 * @var PlaceHolderSet 
	 */
	private $finalPlaceHolderSet;
	
	/**
	 * @var PlaceHolderSet
	 */
	private $parentPlaceHolderSet;

	/**
	 * @param Entity\Abstraction\Page $page
	 */
	public function __construct(Entity\Abstraction\Page $page = null)
	{
		$this->page = $page;
		
		if (isset($page)) {
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
		if (isset($this->page)) {
			
			$placeHolderName = $placeHolder->getName();
			
			// Add to final in cases when it's the page place or locked one
			if ($placeHolder->getLocked() || $placeHolder->getMaster() == $this->page) {
				
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
	 * The list of final (locked or belongs to the final master) placeholders.
	 * The block list will be taken from these placeholders.
	 * @return PlaceHolderSet
	 */
	public function getFinalPlaceHolders()
	{
		return $this->finalPlaceHolderSet;
	}
	
	/**
	 * The list of placeholders which are parents of final placeholders.
	 * The locked blocks will be searched within these placeholders.
	 * @return PlaceHolderSet
	 */
	public function getParentPlaceHolders()
	{
		return $this->parentPlaceHolderSet;
	}
}
