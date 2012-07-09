<?php

namespace Supra\Controller\Pages\Twig;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Exception\FormException;
use Supra\Controller\Pages\BlockController;
use Supra\Form\Configuration\FormBlockControllerConfiguration;
use Supra\Form\Configuration\FormFieldConfiguration;
use Supra\Form\FormBlockController;
use Symfony\Component\Form\Form;

class FormExtension
{
	/**
	 * @var FormBlockController
	 */
	protected $blockController;

	public function __construct(FormBlockController $blockController)
	{
		$this->blockController = $blockController;
	}

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
	 * Generates form begginning tag
	 * @param FormView $view
	 * @param array $options
	 * @param array $attributes
	 * @return \Supra\Html\HtmlTagStart 
	 */
	public function begin(FormView $view, array $options = array(), array $attributes = array())
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

		return new \Twig_Markup($tag->toHtml());
	}

	public function errors(FormView $view, array $options = array(), array $attributes = array())
	{
		$errors = $view->get('errors', array());
		$html = '';
		
		foreach ($errors as $error) {
			$html .= $this->getErrorString($error);
		}
		
		return $html;
	}

	public function error(FormView $view, array $options = array(), array $attributes = array())
	{

		// proccess only fields, not entire form errors
		if ( ! $view->hasParent()) {
			return;
		}

		$vars = $view->getVars();

		if (empty($vars['errors'])) {
			return;
		}

		$output = null;

		foreach ($vars['errors'] as $error) {
			$output .= $this->getErrorString($error, $vars['name']);
		}

		return new \Twig_Markup($output);
	}

	private function getErrorString($error, $name)
	{
		/* @var $error \Symfony\Component\Form\FormError */
		$tag = new \Supra\Html\HtmlTag('span');
		$tag->forceTwoPartTag(true);
		$tag->setAttribute('class', 'error');

		$propertyName = FormBlockControllerConfiguration::generateEditableName(
						FormBlockControllerConfiguration::FORM_GROUP_ID_ERROR, $name)
				. "_{$error->getMessage()}";

		$errorProperty = null;

		if ($this->blockController->hasProperty($propertyName)) {
			$errorProperty = $this->blockController->getPropertyValue($propertyName);
		}

		$message = $error->getMessage();
		if ( ! empty($errorProperty)) {
			$message = strtr($errorProperty, $error->getMessageParameters());
		}

		$tag->setContent($message);

		return $tag->toHtml();
	}

	public function label(FormView $view, array $options = array(), array $attributes = array())
	{
		$vars = $view->getVars();

		$label = $this->blockController->getPropertyValue(
				FormBlockControllerConfiguration::generateEditableName(
						FormBlockControllerConfiguration::FORM_GROUP_ID_LABELS, $vars['name']
				)
		);

		$tag = new \Supra\Html\HtmlTag('label');
		$names = $this->getFormViewParentNames($view);

		$tag->setAttribute('for', 'id_' . implode('_', $names));
		$tag->forceTwoPartTag(true);
		$tag->setContent($label ? $label : $vars['label']);

		return new \Twig_Markup($tag->toHtml());
	}

	public function field(FormView $view, array $options = array(), array $attributes = array())
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
		$tag->setAttribute('id', 'id_' . implode('_', $names));

		if ( ! empty($vars['value'])) {
			$tag->setAttribute('value', $vars['value']);
		}

		return new \Twig_Markup($tag->toHtml());
	}

	public function row(FormView $view, array $options = array(), array $attributes = array())
	{
		$labelTag = $this->label($view, $options, $attributes);
		$fieldTag = $this->field($view, $options, $attributes);
		$errorTag = $this->error($view, $options, $attributes);
		
		return new \Twig_Markup($labelTag->__toString() 
				. $fieldTag->__toString()
				. $errorTag->__toString());
	}

	public function submit(array $options = array(), array $attributes = array())
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

		return new \Twig_Markup($this->setTagAttributes($htmlTag, $options + $attributes)->toHtml());
	}

	public function end()
	{
		return new \Twig_Markup('</form>');
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

	/**
	 * Returns configuration field
	 * 
	 * @param string $fieldName
	 * @return FormFieldConfiguration
	 */
	protected function getConfigurationField($fieldName)
	{
		$conf = $this->blockController->getConfiguration();
		foreach ($conf->fields as $field) {
			if ($fieldName == $field->name) {
				return $field;
			}
		}

		return null;
	}

}
