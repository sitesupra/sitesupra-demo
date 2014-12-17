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
	 * @var string
	 */
	protected $templateName;

	/**
	 * @var Templating
	 */
	protected $templating;

	/**
	 * @var array
	 */
	protected $parameters = array();

	public function __sleep()
	{
		return array('templateName', 'parameters', 'context', 'output');
	}

	public function __wakeup()
	{
	}


	/**
	 * @param Block $block
	 * @param string $templateName
	 * @param Templating $templating
	 */
	public function __construct(Block $block, $templateName, Templating $templating)
	{
		$this->block = $block;
		$this->templateName = $templateName;
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
	 * @param string $templateName
	 */
	public function setTemplateName($templateName)
	{
		$this->templateName = $templateName;
		return $this;
	}

	/**
	 * Renders template and outputs it into response.
	 */
	public function render()
	{
		$this->output(
				$this->templating->render(
						$this->templateName,
						$this->parameters
				)
		);
	}
}