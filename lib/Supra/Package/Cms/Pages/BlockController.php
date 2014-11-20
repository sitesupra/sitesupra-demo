<?php

namespace Supra\Package\Cms\Pages;

use Symfony\Component\HttpFoundation\Request;
use Supra\Core\Controller\Controller;
use Supra\Core\Templating\Templating;
use Supra\Core\DependencyInjection\ContainerAware;
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
use Supra\Package\Cms\Pages\Editable\Filter;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;
use Supra\Package\Cms\Pages\Twig\TwigSupraBlockGlobal;
use Supra\Package\Cms\Pages\Twig\TwigSupraPageGlobal;

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
	 * @var Request
	 */
	protected $request;

	/**
	 * @var ResponsePart
	 */
	protected $response;

	/**
	 * Exception ocurred on prepare/execute.
	 * 
	 * @var \Exception
	 */
	private $exception;

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
		return $this->exception !== null;
	}

	/**
	 * Prepares controller for execution.
	 *
	 * This method is final, use doPrepare for defining actions in prepare step.
	 * 
	 * @param PageRequest $request
	 */
	final public function prepare(PageRequest $request)
	{
		$this->properties = $request->getBlockPropertySet()
				->getBlockPropertySet($this->block);

		$this->request = $request;
		
		$this->response = $this->createResponse($request);
		
		$page = $request->getPage();
		$this->setPage($page);

		try {
			$this->doPrepare();
		} catch (\Exception $e) {
			$this->exception = $e;
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
//							$pageTitle = $request->getLocalization()
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
		if ($this->hadException()) {
			$this->setExceptionResponse($this->exception);
			return null;
		}

		// @TODO: implement similar functionality?
//		$className = get_class($this);
//		$file = Loader::getInstance()->findClassPath($className);
//		$this->getResponse()->addResourceFile($file);

		try {
			$this->doExecute();
		} catch (\Exception $e) {

			$this->container->getLogger()->error($e);
			$this->exception = $e;

			$this->setExceptionResponse($e);
		}
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
					$request->getLocalization()
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
//					$property->setLocalization($request->getLocalization());
//				}
//			}
//			//		else {
//			//			//TODO: should we overwrite editable content parameters from the block controller config?
//			//			$property->setEditable($editable);
//			//		}
//		}
//
//		$editable = $property->getEditable();

		return $property;
	}

	/**
	 * Add additional filters for the property
	 * @param Entity\BlockProperty $property
	 * @param EditableInterface $editable
	 */
	protected function configureContentFilters(BlockProperty $property, EditableInterface $editable)
	{
//		$propertyId = $property->getId();
//
//		if (array_key_exists($propertyId, $this->configuredBlockProperties)) {
//			return;
//		}

		$filters = array();

		// @TODO: or get EM from request object?
		$entityManager = $this->request instanceof PageRequestEdit
				? $this->container->getDoctrine()->getManager('cms')
				: $this->container->getDoctrine()->getManager();

		// Html content filters
		if ($editable instanceof Editable\Html) {
			$filters[] = $this->request instanceof PageRequestEdit
					? new Filter\EditableHtmlFilter()
					: new Filter\HtmlFilter();

		// Editable Inline String
		} elseif ($editable instanceof Editable\InlineString) {
			if ($this->request instanceof PageRequestEdit) {
				$filters[] = new Filter\EditableInlineStringFilter();
			}
		// Textarea and Inline Textarea
		} elseif ($editable instanceof Editable\Textarea
				|| $editable instanceof Editable\InlineTextarea) {

			$filters[] = new Editable\Filter\TextareaFilter();

			if ($this->request instanceof PageRequestEdit
					&& $editable instanceof Editable\InlineTextarea) {
				
				$filters[] = new Filter\EditableInlineTextareaFilter();
			}
		}
		elseif ($editable instanceof Editable\Link) {
			$filters[] = new Filter\LinkFilter($entityManager);
		}
		elseif ($editable instanceof Editable\DateTime) {
			$filters[] = new Editable\Filter\DateTimeFilter();
		}
		elseif ($editable instanceof Editable\Image) {
			$filters[] = new Filter\ImageFilter();
		}
		elseif ($editable instanceof Editable\Gallery) {
			$filters[] = new Filter\GalleryFilter();
		}
		
//		else if ($editable instanceof Editable\Gallery) {
//			$filter = new Filter\GalleryFilter();
////			ObjectRepository::setCallerParent($filter, $this);
//			$filter->property = $property;
//			$filter->request = $this->request;
//			$editable->addFilter($filter);
//		}
//
//		else if ($editable instanceof Editable\Video) {
//			$filter = new Filter\VideoFilter();
////			ObjectRepository::setCallerParent($filter, $this);
//			$filter->property = $property;
//			$editable->addFilter($filter);
//		}
//
////		else if ($editable instanceof Editable\InlineMap) {
////			$filter = new Filter\InlineMapFilter();
////			ObjectRepository::setCallerParent($filter, $this);
////			$filter->property = $property;
////			$editable->addFilter($filter);
////		}
//
//		else if ($editable instanceof Editable\Slideshow) {
//			$filter = new Filter\SlideshowFilter();
////			ObjectRepository::setCallerParent($filter, $this);
//			$filter->property = $property;
//			$editable->addFilter($filter);
//		}
//
//		else if ($editable instanceof Editable\MediaGallery) {
//			$filter = new Filter\MediaGalleryFilter();
////			ObjectRepository::setCallerParent($filter, $this);
//			$filter->property = $property;
//			$editable->addFilter($filter);
//		}
//
//		else if ($editable instanceof Editable\InlineMedia) {
////			if ($this->page->isBlockPropertyEditable($property) && ($this->request instanceof PageRequestEdit)) {
//			if ($this->request instanceof PageRequestEdit) {
//				$filter = new Filter\EditableInlineMedia();
//			} else {
//				$filter = new Filter\InlineMediaFilter();
//			}
//
////			ObjectRepository::setCallerParent($filter, $this);
//			$filter->property = $property;
//			$editable->addFilter($filter);
//		}
//
//		$this->configuredBlockProperties[$propertyId] = true;

		foreach ($filters as $filter) {

			if ($filter instanceof ContainerAware) {
				$filter->setContainer($this->container);
			}

			if ($filter instanceof BlockPropertyAware) {
				$filter->setBlockProperty($property);
			}

			$editable->addFilter($filter);
		}
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

		$propertyConfiguration = $this->getConfiguration()
				->getProperty($name);

		$editable = clone $propertyConfiguration->getEditable();

		// @TODO: do this some way better.
		$this->configureContentFilters($property, $editable);

		$editable->setRawValue($property->getValue());

		return $editable->getFilteredValue();

//		$editableClass = $property->getEditableClass();
//
//		$editable = $property->getEditable();
//		$value = $editable->getFilteredValue();
//
//		return $value;
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
	 * @return BlockConfiguration
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
		return 'Cms:block/exception.html.twig';
	}
	
	/**
	 * @param \Exception $exception
	 */
	protected function setExceptionResponse(\Exception $exception)
	{
		if (! $this->getRequest() instanceof PageRequestEdit) {
			return;
		}

		$response = $this->getResponse();

		if ($response instanceof BlockResponse) {
			
			$response->cleanOutput();

			$response->assign('blockName', $this->getConfiguration()->getTitle())
					->outputTemplate($this->getExceptionResponseTemplateFilename());
		}
	}

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
	 * @param Request $request
	 * @return BlockResponse
	 */
	protected function createResponse(Request $request)
	{
		// @TODO: do it in another way.
		$templating = new Templating();

		$templating->setContainer($this->container);

		$pageTwigGlobal = new TwigSupraPageGlobal();
		$pageTwigGlobal->setContainer($this->container);
		$pageTwigGlobal->setRequest($request);

		// @FIXME: need real response context here!
		$pageTwigGlobal->setResponseContext(new Response\ResponseContext());

		$templating->addGlobal('supra', $pageTwigGlobal);
		$templating->addGlobal('supraBlock', new TwigSupraBlockGlobal($this));

		return $request instanceof PageRequestEdit
				? new BlockResponseEdit($this->block, $templating)
				: new BlockResponseView($this->block, $templating);
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
}
