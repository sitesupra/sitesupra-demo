<?php

namespace Supra\Package\Cms\Pages\Layout\Theme;

interface ThemeInterface
{
	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return ThemeLayoutInterface[]
	 */
	public function getLayouts();
	
	/**
	 * @param string $name
	 * @return ThemeLayoutInterface
	 */
	public function getLayout($name);

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasLayout($name);
}
