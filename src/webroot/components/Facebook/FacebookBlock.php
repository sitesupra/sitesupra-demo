<?php

namespace Project\Facebook;

use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Editable;

/**
 * Feedback block
 */
class FacebookBlock extends BlockController
{

	public function execute()
	{
		$response = $this->getResponse();
		$response->output('Facebook block');
	}

	public function getPropertyDefinition()
	{
		$properties = array();

		
		$html = new Editable\Select('Available pages');
		
		$em = ObjectRepository::getEntityManager($this);
		$query = $em->createQuery('SELECT p FROM Supra\User\Entity\UserFacebookPage p JOIN p.userData ud WHERE ud.active = :active');
		$query->setParameter('active', true);
		$databasePages = $query->getResult();
		
		$values = array();
		
		foreach ($databasePages as $page) {
			/* @var $page \Supra\User\Entity\UserFacebookPage */
			$values[$page->getPageId()] = $page->getPageTitle();
		}
		
		$html->setValues($values);
		$properties['available_pages'] = $html;

		$html = new Editable\LabelString('Facebook tab name');
		$properties['tab_name'] = $html;
		
		return $properties;
	}

}