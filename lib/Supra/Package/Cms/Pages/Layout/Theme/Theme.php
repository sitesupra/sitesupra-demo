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
	 * @return ThemeLayoutInterface[]
	 */
	public function getLayouts()
	{
		return $this->layouts;
	}

	/**
	 * @param string $name
	 * @param string $title
	 * @param string $fileName
	 */
	public function addLayout($name, $title, $fileName)
	{
		if ($this->hasLayout($name)) {
			throw new \RuntimeException(sprintf(
				'Theme [%s] already has layout [%s]',
				$this->getName(),
				$name
			));
		}

		$this->layouts[$name] = new Layout($name, $title, $fileName);
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