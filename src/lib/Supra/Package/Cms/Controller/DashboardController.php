<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class DashboardController extends Controller
{
	protected $application = 'cms_dashboard';

	public function indexAction()
	{
		return $this->renderResponse('index.html.twig');
	}

	public function applicationsListAction()
	{
		return new JsonResponse(array (
			'status' => 1,
			'data' =>
				array (
					'applications' =>
						array (
							0 =>
								array (
									'title' => 'Pages',
									'id' => 'Supra\\Cms\\ContentManager',
									'icon' => '/cms/lib/supra/img/apps/pages_90x90.png',
									'path' => '/supra/content-manager',
								),
							1 =>
								array (
									'title' => 'Files',
									'id' => 'Supra\\Cms\\MediaLibrary',
									'icon' => '/cms/lib/supra/img/apps/media_library_90x90.png',
									'path' => '/supra/media-library',
								),
							2 =>
								array (
									'title' => 'Backoffice Users',
									'id' => 'Supra\\Cms\\InternalUserManager\\InternalUserManagerController',
									'icon' => '/cms/lib/supra/img/apps/backoffice_users_90x90.png',
									'path' => '/supra/internal-user-manager',
								),
							3 =>
								array (
									'title' => 'Banners',
									'id' => 'Supra\\Cms\\BannerManager',
									'icon' => '/cms/lib/supra/img/apps/banners_90x90.png',
									'path' => '/supra/banner-manager',
								),
							4 =>
								array (
									'title' => 'Audit log',
									'id' => 'Supra\\Cms\\AuditLog',
									'icon' => '/cms/lib/supra/img/apps/audit_90x90.png',
									'path' => '/supra/audit-log',
								),
						),
				),
			'error_message' => NULL,
			'warning_message' =>
				array (
				),
			'permissions' => NULL,
		));
	}
}
