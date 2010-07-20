<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Page and template place holder data abstraction
 * @MappedSuperclass
 */
abstract class PlaceHolder extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var int
	 */
	protected $id;

	/**
	 * @ManyToOne(targetEntity="LayoutPlaceHolder")
	 * @JoinColumn(name="layout_place_holder_id")
	 * @var LayoutPlaceHolder
	 * NOTE: removed because decided to specify layout place holder by name not object so layout could be changed with no hassle
	protected $layoutPlaceHolder;
	 */

	/**
	 * @Column(name="name", type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @var Collection
	 */
	protected $blocks;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->blocks = new ArrayCollection();
	}

	/**
	 * Set layout place holder name
	 * @param string $Name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Get layout place holder name
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Set master object
	 * @param Page $master
	 */
	abstract public function setMaster(Page $master);

	/**
	 * Place holder locked status always is false for pages
	 * @return boolean
	 */
	public function getLocked()
	{
		return false;
	}

	/**
	 * Get blocks
	 * @return Collection
	 */
	public function getBlocks()
	{
		return $this->blocks;
	}

	/**
	 * Adds a block
	 * @param Block $block
	 */
	public function addBlock(Block $block)
	{
		if ($this->lock('block')) {
			$this->checkBlock($block);
			if ($this->addUnique($this->blocks, $block, 'id')) {
				$block->setPlaceHolder($this);
			}
			$this->unlock('block');
		}
	}
	
	/**
	 * Checks block object instance
	 * @param $block Block
	 * @throws Exception on failure
	 */
	abstract protected function checkBlock(Block $block);
	
}