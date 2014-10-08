<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\Controller\Controller;
use Supra\Core\HttpFoundation\SupraJsonResponse;
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
		$applications = $this->container->getApplicationManager()->getApplications();

		$applicationData = array();

		foreach ($applications as $application) {
			//unroutable or private apps do not fit here
			if (!$application->getRoute() || !$application->isPublic()) {
				continue;
			}

			$applicationData[] = array(
				'id' => $application->getId(),
				'title' => $application->getTitle(),
				'icon' => $application->getIcon(),
				'path' => $this->container->getRouter()->generate($application->getRoute())
			);
		}

		return new SupraJsonResponse(
			array (
				'applications' => $applicationData
			)
		);
	}
}
