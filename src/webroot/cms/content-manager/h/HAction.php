<?php

namespace Supra\Cms\ContentManager\H;

use Supra\Cms\ContentManager\Root\RootAction;

/**
 * History action, executes root action
 */
class HAction extends RootAction
{
	protected $notFoundAction = 'index';
}
