<?php

namespace Supra\Package\Cms\Pages\Layout\Theme;

/**
 * Abstract theme implementation.
 */
abstract class Theme implements ThemeInterface
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var ThemeLayoutInterface[]
	 */
	protected $layouts = array();

	/**
	 * @param array $layouts
	 */
	public function __construct(array $layouts = array())
	{
		foreach ($layouts as $layout) {
			$this->addLayout($layout);
		}
	}

	/**
	 * @return ThemeLayoutInterface[]
	 */
	public function getLayouts()
	{
		return $this->layouts;
	}

	/**
	 * @param ThemeLayoutInterface $themeLayout
	 * @throws \LogicException
	 */
	public function addLayout(ThemeLayoutInterface $themeLayout)
	{
		$name = $themeLayout->getName();

		if ($this->hasLayout($name)) {
			throw new \LogicException(
					"Layout with name [{$name}] already exists."
			);
		}
		
		$this->layouts[$name] = $themeLayout;
	}

	/**
	 * @param string $name
	 * @return ThemeLayoutInterface
	 */
	public function getLayout($name)
	{
		return $this->hasLayout($name) ? $this->layouts[$name] : null;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasLayout($name)
	{
		return isset($this->layouts[$name]);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		if (empty($this->name)) {
			throw new \LogicException('Theme name is not set.');
		}

		return $this->name;
	}
}