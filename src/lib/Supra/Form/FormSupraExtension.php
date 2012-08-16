<?php

namespace Supra\Form;

use Symfony\Component\Form\AbstractExtension;
use Supra\Form\Configuration\FormBlockControllerConfiguration;

/**
 * FormSupraExtension
 */
class FormSupraExtension extends AbstractExtension
{
	/**
	 * @var FormBlockControllerConfiguration
	 */
	private $blockConfiguration;

	public function __construct(FormBlockControllerConfiguration $blockConfiguration)
	{
		$this->blockConfiguration = $blockConfiguration;
	}

	protected function loadTypeGuesser()
	{
		return new FormTypeGuesser($this->blockConfiguration);
	}

	protected function loadTypes()
	{
		return array(
			new Type\FileIdType(),
			new Type\SubmitType(),
			new Type\DecimalType(),
		);
	}


}
