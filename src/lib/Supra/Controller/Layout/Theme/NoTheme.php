<?php

namespace Supra\Controller\Layout\Theme;

use Supra\Controller\Layout\Exception;

class NoTheme implements ThemeInterface
{

	public function getName()
	{
		return 'noTheme';
	}

	public function getLayoutRoot()
	{
		return SUPRA_TEMPLATE_PATH;
	}

	public function isEnabled()
	{
		return true;
	}

	public function getActiveParameters()
	{
		return array();
	}

	public function getPreviewParameters()
	{
		return array();
	}

	public function getParameterConfigurations()
	{
		return array();
	}

	public function getDescription()
	{
		return '';
	}

	public function makePreviewParametersActive()
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

	public function setParameterConfigurations($configurations)
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

	public function setPreviewParameters($parameters)
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

	public function getCurrentParameterValues()
	{
		return array();
	}

	public function setPreviewParametersAsCurrentParameters()
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

	public function generateCssFiles()
	{
		throw new Exception\RuntimeException('Not implemented.');
	}

}
