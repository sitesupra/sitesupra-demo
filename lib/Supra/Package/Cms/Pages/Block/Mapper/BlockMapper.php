<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

class BlockMapper extends Mapper
{
	public function title($title)
	{
		$this->configuration->setTitle($title);
		return $this;
	}

	public function description($description)
	{
		$this->configuration->setDescription($description);
		return $this;
	}

	public function icon($icon)
	{
		$this->configuration->setIcon($icon);
		return $this;
	}

	public function tooltip($tooltip)
	{
		$this->configuration->setTooltip($tooltip);
		return $this;
	}

	public function group($group)
	{
		$this->configuration->setGroupName($group);
		return $this;
	}

	// @TODO: the rest
}