<?php

namespace Supra\Form\Configuration;

use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

class FormBlockControllerConfiguration extends BlockControllerConfiguration
{
	/**
	 * @var string
	 */
	public $dataClass;

	/**
	 * One of "get" or "post"
	 * @var string
	 */
	public $method = 'post';
}
