<?php

namespace Supra\Package\Cms\Application;

use Supra\Core\Application\AbstractApplication;

class CmsMediaLibraryApplication extends AbstractApplication
{
	protected $id = 'media-library';

	protected $url = 'media-library';

	protected $title = 'Files';

	protected $icon = '/public/cms/supra/img/apps/media_library';

	protected $route = 'media_library';

	protected $access = self::APPLICATION_ACCESS_PUBLIC;
}