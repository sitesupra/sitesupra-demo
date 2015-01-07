<?php

namespace Supra\Package\Cms\Editable;

class Checkbox extends Editable
{
	private $yesLabel = '{#buttons.yes#}';
	private $noLabel = '{#buttons.no#}';
	
	const EDITOR_TYPE = 'Checkbox';
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return self::EDITOR_TYPE;
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
}
