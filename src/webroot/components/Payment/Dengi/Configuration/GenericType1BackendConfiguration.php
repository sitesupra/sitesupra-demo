<?php

namespace Project\Payment\Dengi\Configuration;

use Project\Payment\Dengi;

class GenericType1BackendConfiguration extends BackendConfiguration
{

	/**
	 * 
	 */
	public function configure()
	{
		$this->backendClass = Dengi\Backend\GenericType1Backend::CN();

		parent::configure();
	}

}
