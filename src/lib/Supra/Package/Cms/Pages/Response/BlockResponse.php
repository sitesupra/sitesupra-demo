<?php

namespace Supra\Package\Cms\Pages\Response;

use Supra\Core\Templating\Templating;
use Supra\Package\Cms\Entity\Abstraction\Block;

abstract class BlockResponse extends ResponsePart
{
	/**
	 * @var Block
	 */
	protected $block;

	/**
	 * @var Templating
	 */
	protected $templating;

	/**
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @param Block $block
	 * @param Templating $templating
	 */
	public function __construct(Block $block, Templating $templating)
	{
		$this->block = $block;
		$this->templating = $templating;
	}

	/**
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function assign($key, $value)
	{
		if (is_array($key)) {
			$this->parameters = array_replace($this->parameters, $key);
		} else {
			$this->parameters[$key] = $value;
		}

		return $this;
	}

	/**
	 * @param string $template
	 */
	public function outputTemplate($template)
	{
		$this->output($this->templating->render($template, $this->parameters));
	}
}