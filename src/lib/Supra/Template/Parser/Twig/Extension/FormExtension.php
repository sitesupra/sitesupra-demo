<?php

namespace Supra\Template\Parser\Twig\Extension;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\Util\FormUtil;

class FormExtension extends \Twig_Extension
{
	/**
	 * POST method
	 */
	const METHOD_POST = 'post';

	/**
	 * GET method
	 */
	const METHOD_GET = 'get';

	/**
	 * Default. All characters are encoded before sent (spaces are converted to "+" symbols, and special characters)
	 * are converted to ASCII HEX values
	 */
	const ENCTYPE_URL_ENCODED = 'application/x-www-form-urlencoded';

	/**
	 * No characters are encoded. This value is required when you are using forms that have a file upload control
	 */
	const ENCTYPE_FORM_DATA = 'multipart/form-data';
	/**
	 * Spaces are converted to "+" symbols, but no special characters are encoded
	 */
	const ENCTYPE_TEXT_PLAIN = 'text/plain';

	/**
	 * Twig environment
	 * @var \Twig_Environment 
	 */
	protected $environment;

	/**
	 * {@inheritdoc}
	 */
	public function initRuntime(\Twig_Environment $environment)
	{
		$this->environment = $environment;
	}

	public function getFunctions()
	{
		return array(
			'form_tag' => new \Twig_Function_Method($this, 'renderFormTag', array('is_safe' => array('html'))),
			'form_errors' => new \Twig_Function_Method($this, 'renderErrors', array('is_safe' => array('html'))),
			'form_error' => new \Twig_Function_Method($this, 'renderError', array('is_safe' => array('html'))),
			'form_label' => new \Twig_Function_Method($this, 'renderLabel', array('is_safe' => array('html'))),
			'form_field' => new \Twig_Function_Method($this, 'renderField', array('is_safe' => array('html'))),
			'form_row' => new \Twig_Function_Method($this, 'renderRow', array('is_safe' => array('html'))),
			'form_submit' => new \Twig_Function_Method($this, 'renderSubmit', array('is_safe' => array('html'))),
			'form_end_tag' => new \Twig_Function_Method($this, 'renderFormEndTag', array('is_safe' => array('html'))),
		);
	}

	public function getName()
	{
		return 'supra_form';
	}

	/**
	 * Generates form begginning tag
	 * @param FormView $view
	 * @param array $options
	 * @param array $attributes
	 * @return \Supra\Html\HtmlTagStart 
	 */
	public function renderFormTag(FormView $view, array $options = array(), array $attributes = array())
	{

		$tag = new \Supra\Html\HtmlTagStart('form');

		// defaults
		$method = self::METHOD_POST;
		$enctype = self::ENCTYPE_FORM_DATA;

		$defaultOptions = array(
			'method' => $method,
			'enctype' => $enctype
		);

		$methods = array(self::METHOD_POST, self::METHOD_GET);
		$enctypes = array(self::ENCTYPE_FORM_DATA, self::ENCTYPE_TEXT_PLAIN, self::ENCTYPE_URL_ENCODED);

		if (isset($options['method']) && in_array(strtolower($options['method']), $methods)) {
			$method = strtolower($options['method']);
		}

		if (isset($options['enctype']) && in_array(strtolower($options['enctype']), $enctypes)) {
			$enctype = $options['enctype'];
		}

		if ( ! empty($attributes['class'])) {
			$class = $attributes['class'];

			if (is_string($class)) {
				$tag->addClass(htmlspecialchars($class));
			} elseif (is_array($class)) {
				$classes = join(' ', $class);
				$tag->addClass(htmlspecialchars($classes));
			} else {
				\Log::warn('Exepcted class name as string or as array of strings. Passed value: ', $class);
			}

			unset($attributes['class']);
		}

		$options = $options + $defaultOptions;

		$tagAttributes = $options + $attributes;

		$this->setTagAttributes($tag, $tagAttributes);

		return $tag;
	}

	public function renderErrors(FormView $view, array $options = array(), array $attributes = array())
	{
		return __METHOD__;
	}

	public function renderError(FormView $view, array $options = array(), array $attributes = array())
	{
		return __METHOD__;
	}

	public function renderLabel(FormView $view, array $options = array(), array $attributes = array())
	{
		return __METHOD__;
	}

	public function renderField(FormView $view, array $options = array(), array $attributes = array())
	{
		$vars = $view->getVars();
		// process types and guess field type
		// @TODO
//		foreach ($vars['types'] as $key => $value) {
//			
//		}
		// now will generate only input type text fields

		$names = $this->getFormViewParentNames($view);
		$name = null;

		$firstName = false;
		foreach ($names as $namePart) {
			if ( ! $firstName) {
				$name = $namePart;
				$firstName = true;
				continue;
			}
			
			$name .= "[$namePart]";
		}
		$tag = new \Supra\Html\HtmlTag('input');
		$tag->setAttribute('type', 'text');
		$tag->setAttribute('name', $name);
		$tag->setAttribute('value', $vars['label']);

		return $tag;
	}

	public function renderRow(FormView $view, array $options = array(), array $attributes = array())
	{
		return __METHOD__;
	}

	public function renderSubmit(array $options = array(), array $attributes = array())
	{
		$defaultOptions = array(
			'tag' => 'input',
			'type' => 'submit',
			'value' => 'Submit',
		);

		if (isset($options['tag'])) {
			if (is_string($options['tag'])) {
				$tag = strtolower(trim($options['tag']));
				if ( ! in_array($tag, array('button', 'input'))) {
					unset($options['tag']);
				}
			} else {
				unset($options['tag']);
			}
		}

		$options = $options + $defaultOptions;

		$htmlTag = new \Supra\Html\HtmlTag($options['tag']);

		if ($options['tag'] == 'button') {
			$htmlTag->forceTwoPartTag(true);
			$htmlTag->setContent($options['value']);
			unset($options['value']);
		}

		unset($options['tag']);

		return $this->setTagAttributes($htmlTag, $options + $attributes);
	}

	public function renderFormEndTag()
	{
		return '</form>';
	}

	/**
	 *
	 * @param FormView $view
	 * @param boolean $parentOnly
	 * @return array 
	 */
	protected function getFormViewParentNames(FormView $view, $parentOnly = false)
	{
		$names = array();
		$hasParent = ! is_null($view->getParent());

		do {
			array_unshift($names, trim($view->getName()));

			$hasParent = ! is_null($view->getParent());
			if ($hasParent) {
				$view = $view->getParent();
			}
		} while ($hasParent);

		if ($parentOnly) {
			if (isset($names[0])) {
				return $names[0];
			}

			return null;
		}

		return $names;
	}

	/**
	 * Sets tag attributes
	 * 
	 * @param \Supra\Html\HtmlTagAbstraction $tag
	 * @param array $attributes
	 * @return \Supra\Html\HtmlTagAbstraction 
	 */
	protected function setTagAttributes(\Supra\Html\HtmlTagAbstraction $tag, array $attributes = array())
	{
		foreach ($attributes as $name => $value) {
			if ((is_string($value) || empty($value)) && is_string($name)) {
				$tag->setAttribute($name, $value);
			}
		}

		return $tag;
	}

}
