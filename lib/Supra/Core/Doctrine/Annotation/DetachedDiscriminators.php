<?php

namespace Supra\Core\Doctrine\Annotation;

use Doctrine\Common\Annotations;

/**
 * @Annotations\Annotation
 * @Annotations\Annotation\Target({"CLASS"})
 */
class DetachedDiscriminators
{
	public static function CN()
	{
		return get_called_class();
	}
}