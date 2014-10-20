<?php

namespace Supra\Package\Cms\Application;

use Supra\Core\Application\AbstractApplication;

class CmsPagesApplication extends AbstractApplication
{
	protected $id = 'content-manager';

	protected $url = 'content-manager';

	protected $title = 'Pages';

	protected $icon = '/public/cms/supra/img/apps/pages';

	protected $route = 'cms_pages';

	protected $access = self::APPLICATION_ACCESS_PUBLIC;
}