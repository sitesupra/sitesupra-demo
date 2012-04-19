<?php

namespace Supra\Request;

use Supra\Validator\FilteredInput;

class PostFilesData extends FilteredInput
{
	/**
	 * Override function to avoid is_scalar()
	 * @param string $index
	 * @return boolean
	 */
	public function has($index)
	{
		if ( ! $this->offsetExists($index)) {
			return false;
		}
		
		$value = $this->offsetGet($index);
		
		if ( ! is_null($value) && ! is_array($value)) {
			return false;
		}
		
		return true;
	}
	
}