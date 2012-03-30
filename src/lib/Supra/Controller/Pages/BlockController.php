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
use Supra\Response\TwigResponse;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\Log\Log;
use Supra\Editable;

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
	 * Stores ID values of configured block properties
	 * @var array
	 */
	protected $configuredBlockProperties = array();

	/**
	 * @var \Exception
	 */
	private $hadException = null;

	/**
	 * Loads property definition array
	 * TODO: should be fetched automatically from simple configuration file (e.g. YAML)
	 * @return array
	 */
	public static function getPropertyDefinition()
	{
		return array();
	}

	/**
	 * Prepares controller for execution. This method is final, use doPrepare
	 * for defining actions in prepare step.
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	final public function prepare(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		try {

			parent::prepare($request, $response);

			if ($request instanceof PageRequest) {
				$page = $request->getPage();
				$this->setPage($page);
			}

			$this->doPrepare();
		} catch (\Exception $e) {

			$this->log->error($e);
			$this->hadException = $e;
		}
	}

	/**
	 * Method used by block controllers to implement things to do in this step
	 */
	protected function doPrepare()
	{
		
	}

	/**
	 * @return \Exception|null 
	 */
	public function hadException()
	{
		return $this->hadException;
	}

	/**
	 * Method used by block controllers to implement actual controlling
	 */
	protected function doExecute()
	{
		
	}

	/**
	 * This is called by PageController and has safeguards to catch
	 * unexpected behaviour. Also, does not doExecute() if prepare phase failed
	 * with exception.
	 */
	final public function execute()
	{
		if (empty($this->hadException)) {

			try {
				$this->doExecute();
			} catch (\Exception $e) {

				$this->log->error($e);
				$this->hadException = $e;

				$this->setExceptionResponse($e);
			}
		} else {
			$this->setExceptionResponse($this->hadException);
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
	public function prepareTwigEnvironment()
	{
		$response = $this->getResponse();

		if ($response instanceof Response\TwigResponse) {
			$twig = $response->getTwigEnvironment();

			$helper = new Twig\TwigSupraGlobal();
			$helper->setRequest($this->request);
			$helper->setResponseContext($response->getContext());
			ObjectRepository::setCallerParent($helper, $this);
			$twig->addGlobal('supra', $helper);

			$blockHelper = new Twig\TwigSupraBlockGlobal($this);
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

		foreach ($this->properties as $propertyCheck) {
			/* @var $propertyCheck BlockProperty */
			/* @var $property BlockProperty */
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
		$propertyId = $property->getId();

		if (array_key_exists($propertyId, $this->configuredBlockProperties)) {
			return;
		}

		// Html content additional filters
		if ($editable instanceof Editable\Html) {
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

		if ($editable instanceof Editable\Link) {
			$filter = new Filter\LinkFilter();
			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		if ($editable instanceof Editable\InlineString) {
			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
				$filter = new Filter\EditableString();
				ObjectRepository::setCallerParent($filter, $this);
				$filter->property = $property;
				$editable->addFilter($filter);
			}
		}

		if ($editable instanceof Editable\Textarea) {
			$filter = new Filter\EditableTextarea();
			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}
		
		if ($editable instanceof Editable\Gallery) {
			$filter = new Filter\GalleryFilter();
			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		$this->configuredBlockProperties[$propertyId] = true;
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

	/**
	 * @return string 
	 */
	protected function getExceptionResponseTemplateFilename()
	{
		return 'template/block-exception.html.twig';
	}

	/**
	 * Block controller exception handler
	 * @param \Exception $exception
	 */
	public function setExceptionResponse(\Exception $exception)
	{
		$request = $this->getRequest();

		if ($request instanceof PageRequestView) {
			return;
		}

		$response = $this->getResponse();
		$blockControllerCollection = BlockControllerCollection::getInstance();
		$configuration = $blockControllerCollection->getBlockConfiguration($this->getBlock()->getComponentName());

		if ($response instanceof TwigResponse) {
			$response->cleanOutput();
			$response->setLoaderContext(__CLASS__);
			$response->assign('blockName', $configuration->title);
			$response->outputTemplate($this->getExceptionResponseTemplateFilename());
		}
	}

}
