<?php

namespace Supra\Form;

use Symfony\Component\Form\AbstractExtension;

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

}
