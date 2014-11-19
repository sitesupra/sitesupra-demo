<?php

namespace Supra\Package\Cms\Entity\Abstraction;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Entity\PageBlock;
use Supra\Package\Cms\Entity\TemplateBlock;
use Supra\Package\Cms\Entity\Abstraction\PlaceHolder;
use Supra\Package\Cms\Pages\Response\ResponseContext;

use Supra\Controller\Pages\Exception;

/**
 * Block entity abstraction
 * 
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *		"template"	= "Supra\Package\Cms\Entity\TemplateBlock",
 *		"page"		= "Supra\Package\Cms\Entity\PageBlock"
 * })
 */
abstract class Block extends VersionedEntity implements
		AuditedEntityInterface
{
	/**
	 * @Column(type="string", name="component")
	 * 
	 * @var string
	 */
	protected $componentClass;

	/**
	 * @Column(type="integer")
	 *
	 * @var int
	 */
	protected $position;

	/**
	 * @ManyToOne(targetEntity="PlaceHolder", inversedBy="blocks")
	 * @JoinColumn(name="place_holder_id", referencedColumnName="id")
	 * 
	 * @var PlaceHolder
	 */
	protected $placeHolder;

	/**
	 * Left here just because cascade in remove.
	 *
	 * @OneToMany(
	 *		targetEntity="Supra\Package\Cms\Entity\BlockProperty",
	 *		mappedBy="block",
	 *		cascade={"persist", "remove"}
	 * )
	 * 
	 * @var Collection 
	 */
	protected $blockProperties;

	/**
	 * This property is always false for page block.
	 * 
	 * @Column(type="boolean", nullable=true)
	 * 
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
	 * Get locked value, always false for page blocks.
	 * 
	 * @return boolean
	 */
	public function getLocked()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isLocked()
	{
		return $this->getLocked() === true;
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
		$this->placeHolder = $placeHolder;
		$this->placeHolder->addBlock($this);
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
	 * Get component class name safe for HTML node ID generation.
	 * 
	 * @return string
	 */
	public function getComponentName()
	{
		return $this->getComponentNameFromClassName($this->componentClass);
	}

	/**
	 * @param string $className
	 * @return string
	 */
	public function getComponentNameFromClassName($className)
	{
		return str_replace('\\', '_', $className);
	}

	/**
	 * Set normalized component name, converted to classname.
	 * 
	 * @param string $componentName
	 */
	public function setComponentName($componentName)
	{
		$this->componentClass = str_replace('_', '\\', $componentName);
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
		return in_array(
				$this->getPlaceHolder()->getId(),
				$placeHolderIds,
				true
		);
	}
//
//	/**
//	 * Factory of the block controller
//	 * @return BlockController
//	 */
//	public function createController()
//	{
//// @FIXME: create controller
////		if ( ! Loader\Loader::classExists($componentClass)) {
////			$this->log()->warn("Block component $componentClass was not found for block $this");
////		}
////
////		$blockControllerCollection = BlockControllerCollection::getInstance();
////		$blockController = $blockControllerCollection->createBlockController($componentClass);
////		$blockController->setBlock($this);
////
////		return $blockController;
//	}

//	/**
//	 * @FIXME: move to PageController?
//	 *
//	 * Prepares controller
//	 * @param BlockController $controller
//	 * @param PageRequest $request
//	 * @param ArrayCollection $responseAdditionalData
//	 */
//	public function prepareController(BlockController $controller, PageRequest $request, ResponseContext $responseContext = null)
//	{
//		// Set properties for controller
//		$blockPropertySet = $request->getBlockPropertySet();
//		$blockPropertySubset = $blockPropertySet->getBlockPropertySet($this);
//		$controller->setBlockPropertySet($blockPropertySubset);
//
//		// Create response
//		$response = $controller->createResponse($request);
//
//		if ( ! is_null($responseContext)) {
//			$response->setContext($responseContext);
//		}
//
//		// Prepare
//		$controller->prepare($request, $response);
//
//		$controller->prepareTwigEnvironment();
//	}
//
//	/**
//	 * Executes the controller of the block
//	 * @param BlockController $controller
//	 */
//	public function executeController(BlockController $controller)
//	{
//		// Execute
//		$controller->execute();
//	}

	/**
	 * Creates new instance based on the discriminator of the base entity.
	 *
	 * @param Localization $base
	 * @return Block
	 */
	public static function factory(Localization $base, Block $source = null)
	{
		$block = null;

		switch ($base::DISCRIMINATOR) {
			case self::TEMPLATE_DISCR:
				$block = new TemplateBlock();
				break;
			case self::PAGE_DISCR:
			case self::APPLICATION_DISCR:
				$block = new PageBlock();
				break;
			default:
				throw new \LogicException("Not recognized discriminator value for entity [{$base}].");
		}

		if ($source !== null) {
			$block->setComponentClass($source->getComponentClass());
			$block->setPosition($source->getPosition());

			foreach ($source->getBlockProperties() as $blockProperty) {
				
				$newBlockProperty = clone $blockProperty;
				
				$newBlockProperty->setLocalization($base);
				$newBlockProperty->setBlock($block);

				$block->getBlockProperties()
						->add($newBlockProperty);
			}
		}

		return $block;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersionedParent()
	{
		return $this->placeHolder;
	}
	
}
