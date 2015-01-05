<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Pages\Block\Config\BlockConfig;

abstract class Mapper
{
	/**
	 * @var BlockConfig
	 */
	protected $config;

	public function __construct(BlockConfig $configuration)
	{
		$this->config = $configuration;
	}
}