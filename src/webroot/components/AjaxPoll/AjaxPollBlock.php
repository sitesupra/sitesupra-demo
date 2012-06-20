<?php

namespace Project\AjaxPoll;

use Supra\Controller\Pages\BlockController;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Ajax poll
 */
class AjaxPollBlock extends BlockController
{
	public function doExecute()
	{
		$cache = ObjectRepository::getCacheAdapter($this);
		$data = $cache->fetch(__CLASS__);
		
		if ($data === false) {
			$data = array();
		}
		
		$sessionManager = ObjectRepository::getSessionManager($this);
		$space = $sessionManager->getSpace(AjaxPollSessionSpace::CN);
		/* @var $space AjaxPollSessionSpace */
		
		$response = $this->getResponse();
		
		if ( ! $space->voted) {
			$vote = $this->getRequest()
					->getQueryValue('vote');
			
			if ( ! is_null($vote)) {
				$space->voted = true;
				$data[$vote]++;
				$cache->save(__CLASS__, $data);
			}
			
			$justShow = $this->getRequest()
					->getQueryValue('show');
			
			if ( ! is_null($justShow)) {
				$space->voted = true;
			}
		} else {
			$reset = $this->getRequest()
					->getQueryValue('reset');
			
			if ($reset) {
				$space->voted = false;
			}
		}
		
		$response->assign('voted', $space->voted);
		$response->assign('data', $data);
		
		$response->outputTemplate('index.html.twig');
	}
}
