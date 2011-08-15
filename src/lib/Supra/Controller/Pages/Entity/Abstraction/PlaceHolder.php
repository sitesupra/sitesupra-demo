<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Doctrine\Common\Collections\ArrayCollection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\Entity\PagePlaceHolder;
use Supra\Controller\Pages\Entity\TemplatePlaceHolder;

/**
 * Page and template place holder data abstraction
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\TemplatePlaceHolder", "page" = "Supra\Controller\Pages\Entity\PagePlaceHolder"})
 * @Table(name="su_place_holder")
 */
class PlaceHolder extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var int
	 */
	protected $id;

	/**
	 * FIXME: should be fixed after DDC-482 is done or else there is duplicate
	 *		column for distinguishing the place holder type,
	 *		0: template; 1: page
	 * FIXME: The DDC-482 was done but "INSTANCE OF" was created for WHERE
	 *		conditions only
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $type;

	/**
	 * @Column(name="name", type="string")
	 * @var string
	 */
	protected $name;

	/**
	 * @OneToMany(targetEntity="Block", mappedBy="placeHolder", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $blocks;

	/**
	 * @ManyToOne(targetEntity="Page", inversedBy="placeHolders")
	 * @JoinColumn(name="master_id", referencedColumnName="id", nullable=false)
	 * @var Page
	 */
	protected $master;

	/**
	 * Constructor
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->setName($name);
		$this->blocks = new ArrayCollection();
	}

	/**
	 * Get Id
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set layout place holder name
	 * @param string $Name
	 */
	protected function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Get layout place holder name
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

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
			$this->matchDiscriminator($block);
			if ($this->addUnique($this->blocks, $block)) {
				$block->setPlaceHolder($this);
			}
			$this->unlock('block');
		}
	}
	
	/**
	 * Set master page/template
	 * @param Page $page
	 */
	public function setMaster(Page $master)
	{
		$this->matchDiscriminator($master);
		if ($this->writeOnce($this->master, $master)) {
			$this->master->addPlaceHolder($this);
		}
	}

	/**
	 * @return Page
	 */
	public function getMaster()
	{
		return $this->master;
	}
	
	/**
	 * @return int
	 */
	public function getMaxBlockPosition()
	{
		$blocks = $this->getBlocks();
		$sort = null;
		
		/* @var $block Block */
		foreach ($blocks as $block) {
			$sort = max($sort, $block->getPosition());
		}
		
		return $sort;
	}
	
	/**
	 * Creates new instance based on the discriminator of source entity
	 * @param Entity $base 
	 * @param string $name
	 * @return PlaceHolder
	 */
	public static function factory(Entity $base, $name)
	{
		$discriminator = $base->getDiscriminator();
		$placeHolder = null;
		
		switch ($discriminator) {
			case 'page':
				$placeHolder = new PagePlaceHolder($name);
				break;
			
			case 'template':
				$placeHolder = new TemplatePlaceHolder($name);
				break;
			
			default:
				throw new Exception\LogicException("Not recognized discriminator value for entity {$base}");
		}
		
		return $placeHolder;
	}

}