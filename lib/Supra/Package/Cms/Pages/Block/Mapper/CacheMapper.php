<?php

namespace Supra\Package\Cms\Pages\Block\Mapper;

use Supra\Package\Cms\Entity\Abstraction\Block;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Pages\Response\ResponseContext;

class CacheMapper extends Mapper
{
	protected $lifetime = 3600;

	/**
	 * @return int
	 */
	public function getLifetime()
	{
		return $this->lifetime;
	}

	/**
	 * @param int $lifetime
	 */
	public function setLifetime($lifetime)
	{
		$this->lifetime = $lifetime;
	}

	public function getCacheKey(Localization $localization, Block $block, ResponseContext $context = null)
	{
		return sprintf('supra_block_cache_%s_%s_%s',
			$localization->getId(),
			$block->getId(),
			$this->getContextKey($context)
			);
	}

	protected function getContextKey(ResponseContext $context = null)
	{
		if (!$context) {
			return 'no_context';
		}

		$cacheParts = array();

		$values = $context->getAllValues();

		ksort($values);

		foreach ($values as $value) {
			$cacheParts[] = $value;
		}

		return implode('_', $cacheParts);
	}
}
