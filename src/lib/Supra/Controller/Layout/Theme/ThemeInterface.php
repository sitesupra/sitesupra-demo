<?php

namespace Supra\Controller\Layout\Theme;

interface ThemeInterface
{

	/**
	 * @var array 
	 */
	public function getCurrentParameterSetOutputValues();

	/**
	 * @var string
	 */
	public function getLayoutDir();
}
