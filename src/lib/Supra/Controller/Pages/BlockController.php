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
	 * Loads property definition array
	 * TODO: should be fetched automatically from simple configuration file (e.g. YAML)
	 * @return array
	 */
	abstract public function getPropertyDefinition();
	
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
		$property = null;
		
		if ($this->propertyExists($name)) {
			$property = $this->properties[$name];
		}
		
		$propertyDefinitions = $this->getPropertyDefinition();
		
		if ( ! isset($propertyDefinitions[$name])) {
			throw new Exception\RuntimeException("Content '{$name}' is not defined for block ");
		}
		
		$editable = $propertyDefinitions[$name];

		if ( ! $editable instanceof EditableInterface) {
			throw new Exception\RuntimeException("Definition of property must be an instance of editable");
		}
		
		$newProperty = false;

		if (empty($property)) {
			$newProperty = true;
		} else {
			$propertyType = (string) $property->getType();

			// TODO: must create some feature to cast the property to the new class on upgrades
			if (get_class($editable) != $propertyType) {
				$newProperty = true;
			}
		}
		
		/*
		 * Must create new property here
		 */
		if ($newProperty) {

			$property = new Entity\BlockProperty($name);
			$property->setEditable($editable);
			
			$property->setValue($editable->getDefaultValue());
			$property->setBlock($this->getBlock());

			// Must set some DATA object. Where to get this? And why data is set to property not block?
			//FIXME: should do somehow easier than that
			$property->setLocalization($this->getRequest()->getPageLocalization());
		} else {
			//TODO: should we overwrite editable content parameters from the block controller config?
			$property->setEditable($editable);
		}
		
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
			} else {
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
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param string $name
	 * @return boolean
	 */
	public function propertyExists($name)
	{
		return array_key_exists($name, $this->properties);
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
	
}