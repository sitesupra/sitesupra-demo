<?php

namespace Supra\Controller\Layout\Theme;

interface ThemeInterface
{
	/**
	 * @var string
	 */
	public function getRootDir();

	/**
	 * @var string 
	 */
	public function getUrlBase();
}
