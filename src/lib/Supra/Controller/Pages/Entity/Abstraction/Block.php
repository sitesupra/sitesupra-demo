<?php

namespace Supra\Controller\Pages\Entity\Abstraction;

use Supra\Controller\ControllerAbstraction;
use Supra\Response\ResponseContext;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Entity\BlockProperty;
use Supra\Editable\EditableAbstraction;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\PageBlock;
use Supra\Controller\Pages\Entity\TemplateBlock;
use Supra\Loader;
use Supra\Controller\Pages\BlockControllerCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Block database entity abstraction
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"template" = "Supra\Controller\Pages\Entity\TemplateBlock", "page" = "Supra\Controller\Pages\Entity\PageBlock"})
 */
abstract class Block extends Entity implements AuditedEntityInterface, OwnedEntityInterface
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
	 * @return Collection
	 */
	public function getBlockProperties()
	{
		return $this->blockProperties;
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
		$in = in_array($placeHolderId, $placeHolderIds, true);

		return $in;
	}

	/**
	 * Factory of the block controller
	 * @return BlockController
	 */
	public function createController()
	{
		$blockController = null;
		$componentClass = $this->getComponentClass();

		if ( ! Loader\Loader::classExists($componentClass)) {
			$this->log()->warn("Block component $componentClass was not found for block $this");
		}

		$blockControllerCollection = BlockControllerCollection::getInstance();
		$blockController = $blockControllerCollection->getBlockController($componentClass);
		$blockController->setBlock($this);

		return $blockController;
	}

	/**
	 * Prepares controller
	 * @param BlockController $controller
	 * @param PageRequest $request
	 * @param ArrayCollection $responseAdditionalData
	 */
	public function prepareController(BlockController $controller, PageRequest $request, ResponseContext $responseContext = null)
	{
		// Set properties for controller
		$blockPropertySet = $request->getBlockPropertySet();
		$blockPropertySubset = $blockPropertySet->getBlockPropertySet($this);
		$controller->setBlockPropertySet($blockPropertySubset);

		// Create response
		$response = $controller->createResponse($request);

		if ( ! is_null($responseContext)) {
			$response->setContext($responseContext);
		}

		// Prepare
		$controller->prepare($request, $response);

		$controller->prepareTwigEnvironment();
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
		$discriminator = $base::DISCRIMINATOR;
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

		return $block;
	}

	/**
	 * Doctrine safe clone method with cloning of children
	 */
	public function __clone()
	{
		if ( ! empty($this->id)) {
			$this->regenerateId();
			$this->blockProperties = new ArrayCollection();
			$this->placeHolder = null;
		}
	}

	public function getOwner()
	{
		return $this->placeHolder;
	}

}
