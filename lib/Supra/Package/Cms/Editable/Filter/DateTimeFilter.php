<?php

namespace Supra\Package\Cms\Editable\Filter;

class DateTimeFilter implements FilterInterface
{
	public function filter($content)
	{
		if (! empty($content)) {
			$dateTime = \DateTime::createFromFormat('Y-m-d H:i', $content);
			return $dateTime !== false ? $dateTime : null;
		}
		
		return null;
	}
}
