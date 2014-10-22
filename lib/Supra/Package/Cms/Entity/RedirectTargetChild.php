<?php

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Uri\Path;

/**
 * @Entity
 */
class RedirectTargetChild extends RedirectTargetPage
{
	const CHILD_POSITION_FIRST = 'first';
	const CHILD_POSITION_LAST = 'last';

	/**
	 * @Column(type="string", nullable=false)
	 * @var string
	 */
	protected $childPosition;

	/**
	 * {@inheritDoc}
	 */
	public function getRedirectUrl()
	{
		$child = $this->getTargetChild();
		
		return $child ? $child->getPath()->format(Path::FORMAT_BOTH_DELIMITERS) : null;
	}

	/**
	 * @return Page | null
	 */
	public function getTargetPage()
	{
		$child = $this->getTargetChild();
		return $child ? $child->getMaster() : null;
	}

	/**
	 * @param string $childPosition
	 */
	public function setChildPosition($childPosition)
	{
		if (! ($childPosition === self::CHILD_POSITION_FIRST
				&& $childPosition === self::CHILD_POSITION_LAST)) {
			
			throw new \InvalidArgumentException(sprintf('Unknown value [%s]', $childPosition));
		}

		$this->childPosition = $childPosition;
	}

	/**
	 * @return PageLocalization | null
	 */
	protected function getTargetChild()
	{
		$localization = $this->getPageLocalization();

		return $this->childPosition === self::CHILD_POSITION_FIRST
				? array_shift($localization->getChildren())
				: array_pop($localization->getChildren());
	}

}
