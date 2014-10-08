<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;

abstract class Mapper
{
	/**
	 * @var BlockConfiguration
	 */
	protected $configuration;

	public function __construct(BlockConfiguration $configuration)
	{
		$this->configuration = $configuration;
	}
}