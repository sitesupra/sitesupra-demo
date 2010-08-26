<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Supra\Controller\ControllerAbstraction,
		Supra\Request,
		Supra\Response,
		Doctrine\Common\Collections\ArrayCollection,
		Doctrine\Common\Collections\Collection,
		Supra\Controller\Pages\Exception,
		Supra\Controller\Pages\BlockController;

/**
 * Block database entity abstraction
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\TemplateBlock", "page" = "Supra\Controller\Pages\Entity\PageBlock"})
 * @Table(name="block")
 */
class Block extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $component;

	/**
	 * @Column(type="integer")
	 * @var int
	 */
	protected $position;

	/**
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * @ManyToOne(targetEntity="PlaceHolder", inversedBy="blocks")
	 * @JoinColumn(name="place_holder_id", referencedColumnName="id", nullable=false)
	 * @var PlaceHolder
	 */
	protected $placeHolder;

	/**
	 * @OneToMany(targetEntity="BlockProperty", mappedBy="block", cascade={"persist", "remove"})
	 * @var Collection
	 */
	protected $blockProperties;

	/**
	 * Block controller
	 * @var BlockController
	 */
	protected $controller;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->blockProperties = new ArrayCollection();
	}

	/**
	 * Get locked value, false for page blocks
	 * @return boolean
	 */
	public function getLocked()
	{
		return $this->locked;
	}

	/**
	 * Gets place holder
	 * @return PlaceHolder
	 */
	public function getPlaceHolder()
	{
		return $this->placeHolder;
	}

	/**
	 * Sets place holder
	 * @param PlaceHolder $placeHolder
	 */
	public function setPlaceHolder(PlaceHolder $placeHolder)
	{
		if ($this->writeOnce($this->placeHolder, $placeHolder)) {
			$this->placeHolder->addBlock($this);
		}
		$this->validateLock();
	}

	/**
	 * @return integer
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getComponent()
	{
		return $this->component;
	}

	/**
	 * @param string $component
	 */
	public function setComponent($component)
	{
		$this->component = $component;
	}

	/**
	 * Get order number
	 * @return int
	 */
	public function getPosition()
	{
		return $this->position;
	}

	/**
	 * Set order number
	 * @param int $position
	 */
	public function setPosition($position)
	{
		$this->position = $position;
	}

	/**
	 * @param BlockProperty $blockProperty
	 */
	public function addBlockProperty(BlockProperty $blockProperty)
	{
		if ($this->lock('blockProperties')) {
			if ($this->addUnique($this->blockProperties, $blockProperty)) {
				$blockProperty->setBlock($this);
			}
			$this->unlock('blockProperties');
		}
	}

	/**
	 * Whether the block is inside one of place holder Ids provided
	 * @param array $placeHolderIds
	 * @return boolean
	 */
	public function inPlaceHolder(array $placeHolderIds)
	{
		$placeHolder = $this->getPlaceHolder();
		$placeHolderId = $placeHolder->getId();
		$in = in_array($placeHolderId, $placeHolderIds);
		return $in;
	}

	/**
	 * Validates if the block is not being locked inside unlocked placeholder
	 * @throws Exception if tries to lock block inside unlocked placeholder
	 */
	protected function validateLock()
	{
		if ($this->locked) {
			if (isset($this->placeHolder)) {
				if ( ! $this->placeHolder->getLocked()) {
					$this->locked = false;
					throw new Exception("The block {$this} cannot be locked
							because the place holder {$this->placeHolder} isn't locked");
				}
			}
		}
	}

	/**
	 * Factory of the block controller
	 * @return BlockController
	 */
	public function controllerFactory()
	{
		$component = $this->getComponent();
		if ( ! \class_exists($component)) {
			\Log::swarn("Block component $component was not found for block $block");
			return null;
		}

		$blockController = new $component();
		if ( ! ($blockController instanceof BlockController)) {
			\Log::swarn("Block controller $component must be instance of BlockController in block $block");
			return null;
		}

		$blockController->setBlock($this);

		return $blockController;
	}

	/**
	 * @param BlockController $blockController
	 */
	public function setController(BlockController $blockController)
	{
		$this->controller = $blockController;
	}

	/**
	 * @return BlockController
	 */
	public function getController()
	{
		return $this->controller;
	}
}