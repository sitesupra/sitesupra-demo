<?php

namespace Supra\Package\Cms\Pages;

use Supra\Package\Cms\Pages\Twig\PageExtension;
use Symfony\Component\HttpFoundation\Request;
use Supra\Core\Controller\Controller;
use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Pages\Response\ResponsePart;
use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Pages\Block\BlockExecutionContext;
use Supra\Package\Cms\Pages\Response\BlockResponse;
use Supra\Package\Cms\Pages\Response\BlockResponseView;
use Supra\Package\Cms\Pages\Response\BlockResponseEdit;
use Supra\Package\Cms\Pages\Set\BlockPropertySet;
use Supra\Package\Cms\Editable;
use Supra\Package\Cms\Pages\Editable\Filter;
use Supra\Package\Cms\Pages\Editable\Transformer;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;
use Supra\Package\Cms\Pages\Block\Config;
use Supra\Package\Cms\Pages\Block\BlockPropertyCollectionValue;

/**
 * Block controller abstraction
 */
abstract class BlockController extends Controller
{
	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var ResponsePart
	 */
	protected $response;

	/**
	 * @var Block
	 */
	protected $block;

	/**
	 * @var Config\BlockConfig
	 */
	protected $config;

	/**
	 * @var BlockPropertySet
	 */
	protected $properties;

	/**
	 * Stores ID values of configured block properties
	 * @var array
	 */
	protected $configuredBlockProperties = array();

	/**
	 * Exception occurred on prepare/execute.
	 * 
	 * @var \Exception
	 */
	private $exception;

	/**
	 * @param Block $block
	 * @param Config\BlockConfig $configuration
	 */
	public function __construct(Block $block, Config\BlockConfig $configuration)
	{
		$this->block = $block;
		$this->config = $configuration;
	}

	/**
	 * @return Config\BlockConfig
	 */
	public function getConfiguration()
	{
		return $this->config;
	}

	/**
	 * @return bool
	 */
	public function hadException()
	{
		return $this->exception !== null;
	}

	/**
	 * @return \Exception
	 */
	public function getException()
	{
		return $this->exception;
	}

	/**
	 * @return BlockResponse
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @return PageRequest
	 */
	protected function getRequest()
	{
		return $this->request;
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
		$this->request = $request;
		$this->response = $this->createBlockResponse($request);
		
		$this->properties = $request->getBlockPropertySet()
				->getBlockPropertySet($this->block);

		try {
			$this->doPrepare();
		} catch (\Exception $e) {
			$this->exception = $e;
		}
	}

	/**
	 * Method used by block controllers to implement things to do in this step
	 */
	protected function doPrepare()
	{

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

		$pageExtension = $this->container->getTemplating()->getExtension('supraPage');
		/* @var $pageExtension PageExtension */
		$pageExtension->setBlockExecutionContext(new BlockExecutionContext($this, $this->request));

		try {
			$this->doExecute();
		} catch (\Exception $e) {
			$this->exception = $e;
			$this->setExceptionResponse($e);
		}
	}

	/**
	 * Method used by block controllers to implement actual controlling
	 */
	abstract protected function doExecute();

	/**
	 * Checks if property is known.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasProperty($name)
	{
		return $this->config->getProperty($name) !== null;
	}

	/**
	 * @param string $name
	 * @return BlockProperty
	 */
	public function getProperty($name)
	{
		$propertyConfig = $this->config->getProperty($name);

		$property = null;

		foreach ($this->properties as $candidate) {
			if ($propertyConfig->isMatchingProperty($candidate)
					&& $name === $candidate->getHierarchicalName()) {
				
				$property = $candidate;
				break;
			}
		}

		if ($property === null) {

			$property = $propertyConfig->createProperty($name);

			$property->setBlock($this->block);

			$property->setLocalization(
					$this->getRequest()->getLocalization()
			);

			if ($propertyConfig->hasParent()) {
				$parent = $this->getProperty($propertyConfig->getParent()->getHierarchicalName());
				$parent->addProperty($property);
			}

			$this->properties->append($property);
		}

		return $property;
	}

	/**
	 * Get property value, uses default if not found, throws exception if
	 * property not declared
	 * 
	 * @param string $name
	 * @param array $options
	 * @return mixed
	 */
	public function getPropertyViewValue($name, array $options = array())
	{
		$property = $this->getProperty($name);
		$propertyConfig = $this->config->getProperty($name);

		if ($propertyConfig instanceof Config\PropertyCollectionConfig) {
			return new BlockPropertyCollectionValue($property, $this, $options);
		}

		$editable = $propertyConfig->getEditable()->getInstance();

		$this->configureViewFilters($editable, $property);

		return $editable->toViewValue($property->getValue(), $options);
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getPropertyEditorValue($name)
	{
		$property = $this->getProperty($name);
		$propertyConfig = $this->config->getProperty($name);

		if ($propertyConfig instanceof Config\PropertyCollectionConfig) {

			$value = array();
			
			foreach ($property as $subProperty) {
				/* @var $subProperty BlockProperty */
				$value[$subProperty->getName()] = $this->getPropertyEditorValue($subProperty->getHierarchicalName());
			}

			return $value;
		}

		$editable = $propertyConfig->getEditable()->getInstance();

		$this->configureValueTransformers($editable, $property);

		return $editable->toEditorValue($property->getValue());
	}

	/**
	 * @TODO: must separate manager related code from block controller.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function savePropertyValue($name, $value)
	{
		$propertyConfig = $this->config->getProperty($name);

		if ($propertyConfig instanceof Config\PropertyCollectionConfig) {

			if (! is_array($value)) {
				throw new \UnexpectedValueException('Expecting property collection value to be array.');
			}

			foreach ($value as $subName => $subValue) {
				$this->savePropertyValue($name . '.' . $subName, $subValue);
			}

			return;
		}

		$property = $this->getProperty($name);

		$editable = $propertyConfig->getEditable()->getInstance();

		$this->configureValueTransformers($editable, $property);

		$this->container->getDoctrine()
				->getManager()
				->persist($property);

		$property->setValue($editable->fromEditorValue($value));
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

			// @TODO: use something like BlockExceptionResponse instead to avoid templateName override.
			$response->assign('blockName', $this->getConfiguration()->getTitle())
					->assign('exception', $exception)
					->setTemplateName('Cms:block/exception.html.twig')
					->render();
		}
	}

	/**
	 * @param Request $request
	 * @return BlockResponse
	 */
	protected function createBlockResponse(Request $request)
	{
		return $request instanceof PageRequestEdit
				? new BlockResponseEdit($this->block, $this->config->getTemplateName(), $this->container->getTemplating())
				: new BlockResponseView($this->block, $this->config->getTemplateName(), $this->container->getTemplating());
	}

	/**
	 * {@inheritDoc}
	 * @throws \BadMethodCallException
	 */
	final public function renderResponse($template, $parameters = array())
	{
		throw new \BadMethodCallException('Use BlockController::getResponse()->render() instead.');
	}

	/**
	 * {@inheritDoc}
	 * @throws \BadMethodCallException
	 */
	final public function render($template, $parameters)
	{
		throw new \BadMethodCallException('Use BlockController::getResponse()->render() instead.');
	}

	/**
	 * @TODO: this should be moved to editable configuration.
	 *
	 * @param BlockProperty $property
	 * @param Editable\Editable $editable
	 */
	protected function configureViewFilters(Editable\Editable $editable, BlockProperty $property)
	{
		$propertyId = $property->getId();

		if (array_key_exists($propertyId, $this->configuredBlockProperties)) {
			return;
		}

		$filters = array();

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
			$filters[] = new Filter\LinkFilter();
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
		elseif ($editable instanceof Editable\InlineMap) {
			$filters[] = new Filter\InlineMapFilter();

			if ($this->request instanceof PageRequestEdit) {
				$filters[] = new Filter\EditableInlineMapFilter();
			}
		}

		foreach ($filters as $filter) {

			if ($filter instanceof ContainerAware) {
				$filter->setContainer($this->container);
			}

			if ($filter instanceof BlockPropertyAware) {
				$filter->setBlockProperty($property);
			}

			$editable->addViewFilter($filter);
		}

		$this->configuredBlockProperties[$propertyId] = true;
	}

	/**
	 * @TODO: this should be moved to editable configuration.
	 *
	 * @param Editable\Editable $editable
	 * @param BlockProperty $property
	 */
	protected function configureValueTransformers(Editable\Editable $editable, BlockProperty $property)
	{
		$transformers = array();

		if ($editable instanceof Editable\Html) {
			$transformers[] = new Transformer\HtmlEditorValueTransformer();
		} elseif ($editable instanceof Editable\Link) {
			$transformers[] = new Transformer\LinkEditorValueTransformer();
		} else if ($editable instanceof Editable\Image) {
			$transformers[] = new Transformer\ImageEditorValueTransformer();
		} else if ($editable instanceof Editable\Gallery) {
			$transformers[] = new Transformer\GalleryEditorValueTransformer();
		} else if ($editable instanceof Editable\InlineMap) {
			$transformers[] = new Transformer\ArrayValueTransformer();
		}

		foreach ($transformers as $transformer) {
			if ($transformer instanceof ContainerAware) {
				$transformer->setContainer($this->container);
			}
			if ($transformer instanceof BlockPropertyAware) {
				$transformer->setBlockProperty($property);
			}

			$editable->addEditorValueTransformer($transformer);
		}
	}
}
