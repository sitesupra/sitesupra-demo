<?php

namespace Supra\Package\Cms\Pages;

use Symfony\Component\HttpFoundation\Request;
use Supra\Core\Controller\Controller;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\AbstractPage;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Pages\Set\BlockPropertySet;
use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Response\BlockResponse;
use Supra\Package\Cms\Pages\Response\BlockResponseView;
use Supra\Package\Cms\Pages\Response\BlockResponseEdit;
use Supra\Package\Cms\Editable\EditableInterface;
use Supra\Package\Cms\Editable;
use Supra\Package\Cms\Pages\Filter;

use Supra\Controller\Pages\Twig\TwigSupraBlockGlobal;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;
use Supra\Controller\Pages\Exception;
use Supra\Controller\Exception\StopRequestException;

/**
 * Block controller abstraction
 */
abstract class BlockController extends Controller
{
	/**
	 * @var BlockPropertySet
	 */
	protected $properties = array();

	/**
	 * @var Block
	 */
	protected $block;

	/**
	 * @var AbstractPage
	 */
	protected $page;

	/**
	 * @var BlockConfiguration
	 */
	protected $configuration;

	/**
	 * Stores ID values of configured block properties
	 * @var array
	 */
	protected $configuredBlockProperties = array();

	/**
	 * @var ResponsePart
	 */
	protected $response;

	/**
	 * @var \Exception
	 */
	private $hadException = null;

	/**
	 * @param Block $block
	 * @param BlockConfiguration $configuration
	 */
	public function __construct(
			Block $block,
			BlockConfiguration $configuration
	) {
		$this->block = $block;
		$this->configuration = $configuration;
	}

	/**
	 * @return bool
	 */
	public function hadException()
	{
		return $this->hadException !== null;
	}

	/**
	 * Prepares controller for execution.
	 *
	 * This method is final, use doPrepare for defining actions in prepare step.
	 * 
	 * @param PageRequest $request
	 * @param BlockResponse $response
	 */
	final public function prepare(PageRequest $request, BlockResponse $response)
	{
		$this->request = $request;
		$this->response = $response;
		
		$page = $request->getPage();
		$this->setPage($page);

		try {
			$this->doPrepare();
		} catch (\Exception $e) {
			$this->hadException = $e;
		}

// @FIXME: allow PageController to handle this.
//			$blockClass = $this->getBlock()->getComponentClass();
//
//			$configuration = ObjectRepository::getComponentConfiguration($blockClass);
//
//			if ( ! empty($configuration)) {
//
//				if ($configuration->unique) {
//					// check for uniqness
//					$blockOutputCount = $this->increaseBlockOutputCount();
//
//					if ($blockOutputCount > 1) {
//						$pageTitle = null;
//
//						if ($request instanceof PageRequest) {
//							$pageTitle = $request->getPageLocalization()
//									->getTitle();
//						}
//
//						throw new Exception\RuntimeException("Only one unique block '{$configuration->title}' can exist on a page '$pageTitle'");
//					}
//				}
//			}
	}

	/**
	 * This is called by PageController and has safeguards to catch
	 * unexpected behaviour. Also, does not doExecute() if prepare phase failed
	 * with exception.
	 */
	final public function execute()
	{
		if (empty($this->hadException)) {

// @TODO: implement similar functionality?
//			$className = get_class($this);
//			$file = Loader::getInstance()->findClassPath($className);
//			$this->getResponse()->addResourceFile($file);

			try {
				
				$this->doExecute();

			} catch (\Exception $e) {

//				if ( ! $e instanceof StopRequestException) {
//					$this->log->error($e);
//				}

				$this->hadException = $e;

//				$this->setExceptionResponse($e);
			}
		} else {
//			$this->setExceptionResponse($this->hadException);
		}
	}

	/**
	 * Method used by block controllers to implement things to do in this step
	 */
	protected function doPrepare()
	{
		
	}

	/**
	 * Method used by block controllers to implement actual controlling
	 */
	protected function doExecute()
	{
		
	}

	/**
	 * @return AbstractPage
	 */
	public function getPage()
	{
		return $this->page;
	}

	/**
	 * @param AbstractPage $page
	 */
	public function setPage(AbstractPage $page)
	{
		$this->page = $page;
	}

	/**
	 * @return PageRequest
	 */
	protected function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return BlockResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Assigns supra helper to the twig as global helper
	 */
	public function prepareTwigEnvironment()
	{
// @FIXME: implement if needed.
//		$request = $this->getRequest();
//
//		$response = $this->getResponse();
//
//		if ($response instanceof Response\TwigResponse) {
//
//			$twig = $response->getTwigEnvironment();
//
//			$helper = new Twig\TwigSupraPageGlobal();
//			$helper->setRequest($this->request);
//
//			$theme = $request->getLayout()->getTheme();
//
//			$helper->setTheme($theme);
//			$helper->setResponseContext($response->getContext());
//
//			ObjectRepository::setCallerParent($helper, $this);
//			$twig->addGlobal('supra', $helper);
//
//			$blockHelper = new Twig\TwigSupraBlockGlobal($this);
//			ObjectRepository::setCallerParent($blockHelper, $this);
//			$twig->addGlobal('supraBlock', $blockHelper);
//		}
	}

	/**
	 * @param Set\BlockPropertySet $blockPropertySet
	 */
	public function setBlockPropertySet(Set\BlockPropertySet $blockPropertySet)
	{
		$this->properties = $blockPropertySet;
	}

	/**
	 * Checks if property exists
	 * @param string $name
	 * @return boolean
	 */
	public function hasProperty($name)
	{
		// Find editable by name
		$propertyDefinition = $this->configuration->getProperty($name);

		if ( ! isset($propertyDefinition)) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $name
	 * @return Entity\BlockProperty
	 */
	public function getProperty($name)
	{
		if (! $this->configuration->hasProperty($name)) {
			throw new Exception\RuntimeException(sprintf(
					'Property [%s] is not defined for block [%s]',
					$name,
					get_called_class()
			));
		}

		$configuration = $this->configuration->getProperty($name);

		$editable = $configuration->getEditable();
		$editableClass = get_class($configuration->getEditable());

		$property = null;
		foreach ($this->properties as $possibleProperty) {
			if ($possibleProperty->getName() === $name
					&& $possibleProperty->getEditableClass() === $editableClass) {

				$property = $possibleProperty;
				break;
			}
		}

		// if property were not found, we will create new one.
		if ($property === null) {

			$property = new BlockProperty($name);

			$property->setEditable($editable);

			$property->setBlock($this->getBlock());

			$request = $this->getRequest();

			$property->setLocalization(
					$request->getPageLocalization()
			);

			$defaultValue = $configuration->getDefaultValue($request->getLocale());

			$property->setValue($defaultValue);
		}


//		// Find editable by name
//		$propertyDefinition = $this->configuration->getProperty($name);
//
//			$editable = $propertyDefinition->editableInstance;
//
//			if ( ! $editable instanceof EditableInterface) {
//				throw new Exception\RuntimeException("Definition of property must be an instance of editable");
//			}
//
//			// Find property by name
//			$property = null;
//			$expectedType = get_class($editable);
//
//			foreach ($this->properties as $propertyCheck) {
//				/* @var $propertyCheck BlockProperty */
//				/* @var $property BlockProperty */
//				if ($propertyCheck->getName() === $name) {
//
//					if ($propertyCheck->getType() === $expectedType) {
//						$property = $propertyCheck;
////						$property->setEditable($editable);
////						$property->setValue($editable->getDefaultValue());
//						break;
//					}
//				}
//			}
//
//			/*
//			 * Must create new property here
//			 */
//			if (empty($property)) {
//
//				$property = new Entity\BlockProperty($name);
//				$property->setEditable($editable);
//
//				$request = $this->getRequest();
//				$localeId = null;
//
//				if ($request instanceof PageRequest) {
//					$localeId = $request->getLocale();
//				}
//
//				/* @var $request Request\HttpRequest */
//
//				$defaultValue = array();
//				if ( ! empty($propertyDefinition->properties) && ! $editable instanceof Editable\Gallery) {
//					foreach ($propertyDefinition->properties as $subProperty) {
//						$defaultValue[$subProperty->name] = $subProperty->editableInstance->getDefaultValue();
//					}
//					$defaultValue = array($defaultValue);
//				} else {
//
//					$defaultValue = $editable->getDefaultValue($localeId);
//				}
//
//				$property->setValue($defaultValue);
//				$property->setBlock($this->getBlock());
//
//				// Must set some DATA object. Where to get this? And why data is set to property not block?
//				//FIXME: should do somehow easier than that
//				if ($request instanceof PageRequest) {
//					$property->setLocalization($request->getPageLocalization());
//				}
//			}
//			//		else {
//			//			//TODO: should we overwrite editable content parameters from the block controller config?
//			//			$property->setEditable($editable);
//			//		}
//		}
//
//		$editable = $property->getEditable();

		// @TODO: do this some way better.
		$this->configureContentFilters($property, $editable);

		return $property;
	}

	/**
	 * Add additional filters for the property
	 * @param Entity\BlockProperty $property
	 * @param EditableInterface $editable
	 */
	protected function configureContentFilters(BlockProperty $property, EditableInterface $editable)
	{
		$propertyId = $property->getId();

		if (array_key_exists($propertyId, $this->configuredBlockProperties)) {
			return;
		}

		// Html content additional filters
		if ($editable instanceof Editable\Html) {
			// Editable action
//			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
			if ($this->request instanceof PageRequestEdit) {

				$filter = new Filter\EditableHtml();
				$filter->property = $property;
				$editable->addFilter($filter);
				// View
			} else {
				$filter = new Filter\ParsedHtmlFilter();
//				ObjectRepository::setCallerParent($filter, $this);
				$filter->property = $property;
				$editable->addFilter($filter);
			}

			$context = $this->response->getContext();

			// HTML filter specific
			$filter->setResponseContext($context);
		}

		else if ($editable instanceof Editable\Link) {
			$filter = new Filter\LinkFilter();
//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		else if ($editable instanceof Editable\InlineString) {
//			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
			if ($this->request instanceof PageRequestEdit) {
				$filter = new Filter\EditableString();
//				ObjectRepository::setCallerParent($filter, $this);
				$filter->property = $property;
				$editable->addFilter($filter);
			}
		}

		else if ($editable instanceof Editable\Textarea) {
			$filter = new Filter\EditableTextarea();
//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		else if ($editable instanceof Editable\InlineTextarea) {
			$filter = new Filter\InlineTextareaFilter();
//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		else if ($editable instanceof Editable\Gallery) {
			$filter = new Filter\GalleryFilter();
//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$filter->request = $this->request;
			$editable->addFilter($filter);
		}

		else if ($editable instanceof Editable\Video) {
			$filter = new Filter\VideoFilter();
//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

//		else if ($editable instanceof Editable\InlineMap) {
//			$filter = new Filter\InlineMapFilter();
//			ObjectRepository::setCallerParent($filter, $this);
//			$filter->property = $property;
//			$editable->addFilter($filter);
//		}

		else if ($editable instanceof Editable\Slideshow) {
			$filter = new Filter\SlideshowFilter();
//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		else if ($editable instanceof Editable\MediaGallery) {
			$filter = new Filter\MediaGalleryFilter();
//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		else if ($editable instanceof Editable\InlineMedia) {
//			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
			if ($this->request instanceof PageRequestEdit) {
				$filter = new Filter\EditableInlineMedia();
			} else {
				$filter = new Filter\InlineMediaFilter();
			}

//			ObjectRepository::setCallerParent($filter, $this);
			$filter->property = $property;
			$editable->addFilter($filter);
		}

		$this->configuredBlockProperties[$propertyId] = true;
	}

	/**
	 * Get property value, uses default if not found, throws exception if
	 * property not declared
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

//	/**
//	 * Set the block which called the controller.
//	 *
//	 * @param Block $block
//	 */
//	public function setBlock(Block $block)
//	{
//		$this->block = $block;
//	}

	/**
	 * Get the block which created the controller.
	 * 
	 * @return Block
	 */
	public function getBlock()
	{
		return $this->block;
	}
//
//	/**
//	 * @param BlockControllerConfiguration $configuration
//	 */
//	public function setConfiguration(BlockControllerConfiguration $configuration)
//	{
//		$this->configuration = $configuration;
//	}

	/**
	 * @return BlockControllerConfiguration
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

//	/**
//	 * @return string
//	 */
//	protected function getExceptionResponseTemplateFilename()
//	{
//		return 'template/block-exception.html.twig';
//	}
//
//	/**
//	 * Block controller exception handler
//	 * @param \Exception $exception
//	 */
//	public function setExceptionResponse(\Exception $exception)
//	{
//		$request = $this->getRequest();
//
//		if ($request instanceof PageRequestView) {
//			return;
//		}
//
//		$response = $this->getResponse();
//
//		if ($response instanceof TwigResponse) {
//			$blockClass = $this->getBlock()->getComponentName();
//			$configuration = ObjectRepository::getComponentConfiguration($blockClass);
//
//			$response->cleanOutput();
//			$response->setLoaderContext(__CLASS__);
//			$response->assign('blockName', $configuration->title);
//			$response->outputTemplate($this->getExceptionResponseTemplateFilename());
//		}
//	}

//	/**
//	 * Block controller local counter. Usually might be used to count the number
//	 * of the block instances in the page.
//	 *
//	 * @return integer
//	 */
//	private function increaseBlockOutputCount()
//	{
//		$blockClassName = get_class($this);
//		$offset = 'BLOCK_COUNTER_' . $blockClassName;
//		$response = $this->getResponse();
//
//		if ( ! $response instanceof Response\HttpResponse) {
//			return null;
//		}
//
//		$count = 0;
//		$context = $response->getContext();
//
//		if (isset($context[$offset])) {
//			$count = max((int) $context[$offset], 0);
//		}
//
//		$count ++;
//		$context[$offset] = $count;
//
//		return $count;
//	}

	/**
	 * @TODO: maybe we should create response in PageController?
	 * 
	 * @return BlockResponse
	 */
	public function createResponse(PageRequest $request)
	{
		$templating = $this->container->getTemplating();

		$templating->addGlobal('supraBlock', new TwigSupraBlockGlobal($this));

		if ($request instanceof PageRequestEdit) {
			return new BlockResponseEdit($this->block, $templating);
		} else {
			return new BlockResponseView($this->block, $templating);
		}
	}
}
