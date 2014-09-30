<?php

namespace Supra\Package\Cms\Application;

use Supra\Core\Application\AbstractApplication;

class CmsPagesApplication extends AbstractApplication
{
	protected $id = 'content-manager';

	protected $url = 'content-manager';

	protected $title = 'Pages';

	// acts as a prefix
	// fullnames are /public/cms/supra/img/apps/pages_90x90.png
	// /public/cms/supra/img/apps/pages_32x32.png
	protected $icon = '/public/cms/supra/img/apps/pages';

	protected $route = 'cms_pages';

	protected $access = self::APPLICATION_ACCESS_PUBLIC;
}