<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Request;
use Supra\Response;
use Supra\Editable\EditableAbstraction;
use Supra\Editable\EditableInterface;
use Supra\Controller\Pages\Request\HttpEditRequest;
use Supra\Controller\Pages\Response\Block;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Loader;

/**
 * Block controller abstraction
 * @method PageRequest getRequest()
 * @method Response\TwigResponse getResponse()
 */
abstract class BlockController extends ControllerAbstraction
{

	/**
	 * @var Set\BlockPropertySet
	 */
	protected $properties = array();

	/**
	 * Block which created the controller
	 * @var Entity\Abstraction\Block
	 */
	protected $block;

	/**
	 * Current request page
	 * @var Entity\Abstraction\AbstractPage
	 */
	protected $page;

	/**
	 * @var BlockControllerConfiguration
	 */
	protected $configuration;

	/**
	 * Loads property definition array
	 * TODO: should be fetched automatically from simple configuration file (e.g. YAML)
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		return array();
	}

	/**
	 * Prepares controller for execution
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function prepare(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::prepare($request, $response);

		if ($request instanceof PageRequest) {
			$page = $request->getPage();
			$this->setPage($page);
		}
	}

	/**
	 * @return Entity\Abstraction\AbstractPage
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @param Entity\Abstraction\AbstractPage $page
	 */
	public function setPage(Entity\Abstraction\AbstractPage $page)
	{
		$this->page = $page;
	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface $request
	 * @return Response\TwigResponse
	 */
	public function createResponse(Request\RequestInterface $request)
	{
		$response = new Response\TwigResponse($this);

		return $response;
	}

	/**
	 * Assigns supra helper to the twig as global helper
	 */
	public function prepareTwigHelper()
	{
		$response = $this->getResponse();

		if ($response instanceof Response\TwigResponse) {
			$twig = $response->getTwigEnvironment();

			// Now it 
			$helper = new Helper\TwigHelper();
			$helper->setRequest($this->request);
			$helper->setResponseContext($response->getContext());
			ObjectRepository::setCallerParent($helper, $this);
			$twig->addGlobal('supra', $helper);

			$blockHelper = new Helper\TwigBlockHelper($this);
			ObjectRepository::setCallerParent($blockHelper, $this);
			$twig->addGlobal('supraBlock', $blockHelper);
		}
	}

	/**
	 * @param Set\BlockPropertySet $blockPropertySet
	 */
	public function setBlockPropertySet(Set\BlockPropertySet $blockPropertySet)
	{
		$this->properties = $blockPropertySet;
	}

	/**
	 * @param string $name
	 * @return Entity\BlockProperty
	 */
	public function getProperty($name)
	{
		// Find editable by name
		$propertyDefinitions = $this->getPropertyDefinition();

		if ( ! isset($propertyDefinitions[$name])) {
			throw new Exception\RuntimeException("Content '{$name}' is not defined for block ");
		}

		$editable = $propertyDefinitions[$name];

		if ( ! $editable instanceof EditableInterface) {
			throw new Exception\RuntimeException("Definition of property must be an instance of editable");
		}

		// Find property by name
		$property = null;
		$expectedType = get_class($editable);

		$typeChanged = false;
		foreach ($this->properties as $propertyCheck) {
			/* @var $propertyCheck BlockProperty */
			/* @var $property BlockProperty */
//			if ($propertyCheck->getName() === $name
//					&& $propertyCheck->getType() === $expectedType) {
//
//				$property = $propertyCheck;
//				break;
//			}
			if ($propertyCheck->getName() === $name) {
				
				$property = $propertyCheck;
				
				if ($propertyCheck->getType() !== $expectedType) {
					$property->setEditable($editable);
					$property->setValue($editable->getDefaultValue());
				}
				
				break;
			}
		}

		/*
		 * Must create new property here
		 */
		if (empty($property)) {
			
			$property = new Entity\BlockProperty($name);
			$property->setEditable($editable);

			$property->setValue($editable->getDefaultValue());
			$property->setBlock($this->getBlock());

			// Must set some DATA object. Where to get this? And why data is set to property not block?
			//FIXME: should do somehow easier than that
			$property->setLocalization($this->getRequest()->getPageLocalization());
		}
//		else {
//			//TODO: should we overwrite editable content parameters from the block controller config?
//			$property->setEditable($editable);
//		}
		
		$editable = $property->getEditable();

		// This is done in previous line already
//		//TODO: this is ugly content copying
//		$content = $property->getValue();
//		$editable->setContent($content);
		//TODO: do this some way better..
		$this->configureContentFilters($property, $editable);

		return $property;
	}

	/**
	 * Add additional filters for the property
	 * @param Entity\BlockProperty $property
	 * @param EditableInterface $editable
	 */
	protected function configureContentFilters(Entity\BlockProperty $property, EditableInterface $editable)
	{
		// Html content additional filters
		if ($editable instanceof \Supra\Editable\Html) {
			// Editable action
			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
				$filter = new Filter\EditableHtml();
				$filter->property = $property;
				$editable->addFilter($filter);
				// View
			}
			else {
				$filter = new Filter\ParsedHtmlFilter();
				ObjectRepository::setCallerParent($filter, $this);
				$filter->property = $property;
				$editable->addFilter($filter);
			}
		}

		if ($editable instanceof \Supra\Editable\Link) {
			$filter = new Filter\LinkFilter();
			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}
		
		if ($editable instanceof \Supra\Editable\InlineString) {
			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
				$filter = new Filter\EditableString();
				ObjectRepository::setCallerParent($filter, $this);
				$filter->property = $property;
				$editable->addFilter($filter);
			}
		}
	}

	/**
	 * Get property value, use default if not found
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
		$property = $this->getProperty($name);
		$editable = $property->getEditable();
		$value = $editable->getFilteredValue();

		return $value;
	}

	/**
	 * Get the content and output it to the response or return if no response 
	 * object set
	 * 
	 * @TODO could move to block response object
	 * @TODO NB! This isn't escaped currently! Maybe this method doesn't make sense?
	 * 
	 * @param string $name
	 */
	public function outputProperty($name)
	{
		$value = $this->getPropertyValue($name);
		$this->response->output($value);
	}

	/**
	 * Set the block which called the controller
	 * @param Entity\Abstraction\Block $block
	 */
	public function setBlock(Entity\Abstraction\Block $block)
	{
		$this->block = $block;
	}

	/**
	 * Get the block which created the controller
	 * @return Entity\Abstraction\Block
	 */
	public function getBlock()
	{
		// Workaround for not existent block
		//TODO: remove
		if (empty($this->block)) {
			$this->block = new Entity\PageBlock();
		}

		return $this->block;
	}

	/**
	 * @param BlockControllerConfiguration $configuration 
	 */
	public function setConfiguration(BlockControllerConfiguration $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * @return BlockControllerConfiguration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	static function createController()
	{
		$className = self::CN();
		
		$controller = Loader\Loader::getClassInstance($className, 'Supra\Controller\Pages\BlockController');
		
		return $controller;
	}

}