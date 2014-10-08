<?php

namespace Supra\Package\Cms\Pages\Layout\Theme;

interface ThemeLayoutInterface
{
	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return string
	 */
	public function getIcon();

	/**
	 * @return string
	 */
	public function getFileName();
}
