<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Supra\Controller\ControllerAbstraction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Editable\EditableAbstraction;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\PageBlock;
use Supra\Controller\Pages\Entity\TemplateBlock;
use Supra\Loader;

/**
 * Block database entity abstraction
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\TemplateBlock", "page" = "Supra\Controller\Pages\Entity\PageBlock"})
 */
abstract class Block extends Entity
{
	/**
	 * @Column(type="string", name="component")
	 * @var string
	 */
	protected $componentClass;

	/**
	 * @Column(type="integer")
	 * @var int
	 */
	protected $position;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;

	/**
	 * @ManyToOne(targetEntity="PlaceHolder", inversedBy="blocks")
	 * @JoinColumn(name="place_holder_id", referencedColumnName="id")
	 * @var PlaceHolder
	 */
	protected $placeHolder;
	
	/**
	 * Left here just because cascade in remove
	 * @OneToMany(targetEntity="Supra\Controller\Pages\Entity\BlockProperty", mappedBy="block", cascade={"persist", "remove"}) 
	 * @var Collection 
	 */ 
	protected $blockProperties;

	/**
	 * This property is always false for page block
	 * @Column(type="boolean", nullable=true)
	 * @var boolean
	 */
	protected $locked = false;

	/**
	 * Create block properties collection
	 */
	public function __construct()
	{
		parent::__construct();
		$this->blockProperties = new ArrayCollection();
	}
	
	/**
	 * Get locked value, always false for page blocks
	 * @return boolean
	 */
	public function getLocked()
	{
		return false;
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
	}

	/**
	 * @return string
	 */
	public function getComponentClass()
	{
		return $this->componentClass;
	}
	
	/**
	 * @param string $componentClass
	 */
	public function setComponentClass($componentClass)
	{
		$this->componentClass = trim($componentClass, '\\');
	}
	
	/**
	 * Get component class name safe for HTML node ID generation
	 * @return string
	 */
	public function getComponentName()
	{
		$componentName = $this->componentClass;
		$componentName = str_replace('\\', '_', $componentName);
		
		return $componentName;
	}
	
	/**
	 * Set normalized component name, converted to classname
	 * @param string $componentName
	 */
	public function setComponentName($componentName)
	{
		$componentClass = str_replace('_', '\\', $componentName);
		$this->componentClass = $componentClass;
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
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * @param string $locale
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;
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
	 * Factory of the block controller
	 * @return BlockController
	 */
	public function createController()
	{
		$component = $this->getComponentClass();
		if ( ! class_exists($component)) {
			$this->log()->warn("Block component $component was not found for block $this");
			
			return null;
		}

		try {
			$blockController = Loader\Loader::getClassInstance($component, 'Supra\Controller\Pages\BlockController');
			$blockController->setBlock($this);

			return $blockController;
		} catch (Loader\Exception\ClassMismatch $e) {
			$this->log()->warn("Block controller $component must be instance of BlockController in block $this");
			
			return null;
		}
	}
	
	/**
	 * Prepares controller
	 * @param BlockController $controller
	 * @param PageRequest $request
	 */
	public function prepareController(BlockController $controller, PageRequest $request)
	{
		// Set properties for controller
		$blockPropertySet = $request->getBlockPropertySet();
		$blockPropertySubset = $blockPropertySet->getBlockPropertySet($this);
		$controller->setBlockPropertySet($blockPropertySubset);
		
		// Create response
		$response = $controller->createResponse($request);
		
		// Prepare
		$controller->prepare($request, $response);
		
		$controller->prepareTwigHelper();
	}
	
	/**
	 * Executes the controller of the block
	 * @param BlockController $controller
	 */
	public function executeController(BlockController $controller)
	{
		// Execute
		$controller->execute();
	}
	
	/**
	 * Creates new instance based on the discriminator of the base entity
	 * @param Entity $base
	 * @return Block
	 */
	public static function factory(Entity $base)
	{
		$discriminator = $base->getDiscriminator();
		$block = null;
		
		switch ($discriminator) {
			case self::TEMPLATE_DISCR:
				$block = new TemplateBlock();
				break;
			
			case self::PAGE_DISCR:
			case self::APPLICATION_DISCR:
				$block = new PageBlock();
				break;
			
			
			default:
				throw new Exception\LogicException("Not recognized discriminator value for entity {$base}");
		}
		
		return $block;
	}
	
	/**
	 * Creates new instance based on the discriminator of base entity and 
	 * the properties of source entity
	 * @param Entity $base 
	 * @param Block $source
	 * @return Block
	 */
	public static function factoryClone(Entity $base, Block $source)
	{
		$block = self::factory($base);
		
		$block->setComponentClass($source->getComponentClass());
		$block->setPosition($source->getPosition());
		$block->setLocale($source->getLocale());
		
		return $block;
	}

}
