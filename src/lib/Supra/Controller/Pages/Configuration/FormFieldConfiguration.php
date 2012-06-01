<?php

namespace Supra\Controller\Pages\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\Editable\EditableInterface;
use Supra\Editable;

class FormFieldConfiguration implements ConfigurationInterface
{

	/**
	 * Field name
	 * @var string 
	 */
	public $name;

	/**
	 * One of $types
	 * @var string 
	 */
	public $type;

	/**
	 * Allowed field types
	 * @var array 
	 */
	private $types = array(
		'text',
		'textarea',
		'password',
		'checkbox',
		'file',
		'radio',
		'hidden',
	);

	/**
	 * Field label
	 * @var string 
	 */
	public $label;

	/**
	 * Field value
	 * @var string 
	 */
	public $value;

	/**
	 * @return FormFieldConfiguration 
	 */
	public function configure()
	{
		return $this;
	}

}