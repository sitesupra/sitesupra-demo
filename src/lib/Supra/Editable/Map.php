<?php

namespace Supra\Editable;

use Supra\Validator\FilteredInput;
use Supra\Validator\Type\AbstractType;

/**
 * Map
 */
class Map extends EditableAbstraction
{
	private $latitude = '56.952183';
	private $longitude = '24.122286';
	
	const EDITOR_TYPE = 'Map';
	const EDITOR_INLINE_EDITABLE = false;
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return self::EDITOR_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return self::EDITOR_INLINE_EDITABLE;
	}
	
	/**
	 * @return string 
	 */
	public function getLatitude()
	{
		return $this->latitude;
	}

	/**
	 * @param string $latitude 
	 */
	public function setLatitude($latitude)
	{
		$this->latitude = $latitude;
	}

	/**
	 * @return string 
	 */
	public function getLongitude()
	{
		return $this->longitude;
	}

	/**
	 * @param string $longitude 
	 */
	public function setLongitude($longitude)
	{
		$this->longitude = $longitude;
	}

		
	public function getAdditionalParameters()
	{
		return array(
			'value' => array(
				$this->latitude,
				$this->longitude,
			),
		);
	}
	
	/**
	 * Validates and sanitizes the content
	 * @param mixed $content
	 */
	public function setContent($content)
	{
		parent::setContent($content);
	}
	
	/**
	 * Validates and sanitizes the content
	 * @return boolean
	 */
	public function getContent()
	{
		return parent::getContent();
	}
}
