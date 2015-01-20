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

	/**
	 * @param Block $block
	 * @param Templating $templating
	 * @param null|string $templateName
	 */
	public function __construct(Block $block, Templating $templating, $templateName = null)
	{
		$this->block = $block;
		$this->templating = $templating;
		$this->templateName = $templateName;
	}

	/**
	 * @param mixed $key
	 * @param mixed $value
	 * @return $this
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
	 * @return $this
	 */
	public function setTemplateName($templateName)
	{
		$this->templateName = $templateName;
		return $this;
	}

	/**
	 * Renders template and outputs it into response.
	 *
	 * @param array $parameters
	 */
	public function render(array $parameters = array())
	{
		if ($this->templateName === null) {
			throw new \RuntimeException('Template name was not specified, nothing to render.');
		}

		$this->output(
				$this->templating->render(
						$this->templateName,
						array_merge($this->parameters, $parameters)
				)
		);
	}
}