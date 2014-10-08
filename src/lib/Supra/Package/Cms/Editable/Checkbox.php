<?php

namespace Supra\Package\Cms\Editable;

use Supra\Validator\FilteredInput;
use Supra\Validator\Type\AbstractType;

/**
 * Checkbox with configurable Yes/No titles
 */
class Checkbox extends EditableAbstraction
{
	private $yesLabel = '{#buttons.yes#}';
	private $noLabel = '{#buttons.no#}';
	
	const EDITOR_TYPE = 'Checkbox';
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
	
	public function setYesLabel($yesLabel)
	{
		$this->yesLabel = $yesLabel;
	}

	public function setNoLabel($noLabel)
	{
		$this->noLabel = $noLabel;
	}
	
	/**
	 * @return string 
	 */
	public function getYesLabel()
	{
		return $this->yesLabel;
	}

	/**
	 * @return string 
	 */
	public function getNoLabel()
	{
		return $this->noLabel;
	}

	
	public function getAdditionalParameters()
	{				
		$output = array(
			'labels' => array(
				$this->yesLabel,
				$this->noLabel,
			),
		);

		return $output;
	}
	
	/**
	 * Validates and sanitizes the content
	 * @param mixed $content
	 */
	public function setContent($content)
	{
		$content = FilteredInput::validate($content, AbstractType::BOOLEAN);
		
		parent::setContent($content);
	}
	
	/**
	 * Validates and sanitizes the content
	 * @return boolean
	 */
	public function getContent()
	{
		$content = parent::getContent();
		$content = FilteredInput::validate($content, AbstractType::BOOLEAN);
		
		return (string)$content;
	}
}
