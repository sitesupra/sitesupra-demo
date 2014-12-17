<?php


namespace Supra\Package\Cms\Controller;

use SimpleThings\EntityAudit\AuditReader;
use Symfony\Component\HttpFoundation\Request;

class PageHistoryController extends AbstractCmsController
{
	public function loadAction(Request $request)
	{
		$pageId = $request->query->get('page_id');

		$reader = $this->container['entity_audit.manager']->createAuditReader($this->container->getDoctrine()->getManager());
		/* @var $reader AuditReader */

		$history = $reader->getEntityHistory('Supra\Package\Cms\Entity\PageLocalization', $pageId);

		var_dump($history);
	}
}
