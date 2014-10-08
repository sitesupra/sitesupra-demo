<?php

namespace Supra\Package\Cms\Pages\Exception;

use Supra\Package\Cms\Exception\CmsException;

/**
 * Raised when the object is locked by someone else
 */
class ObjectLockedException extends CmsException
{
}
