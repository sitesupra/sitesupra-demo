<?php

namespace Supra\Package\Cms\Application;

use Supra\Core\Application\AbstractApplication;

class CmsInternalUserManagerApplication extends AbstractApplication
{
	protected $id = 'internal-user-manager';

	protected $url = 'internal-user-manager';

	protected $title = 'Backoffice Users';

	protected $icon = '/public/cms/supra/img/apps/backoffice_users';

	protected $route = 'backoffice_users';

	protected $access = self::APPLICATION_ACCESS_PUBLIC;
}