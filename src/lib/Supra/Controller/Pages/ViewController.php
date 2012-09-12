<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Response;
use Supra\Response\ResponseInterface;
use Supra\Request\RequestInterface;
use Supra\Controller\Layout;
use Doctrine\ORM\EntityManager;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Response\PlaceHolder;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Uri\Path;
use Supra\Response\ResponseContext;
use Supra\Response\ResponseContextLocalProxy;
use Supra\Controller\Pages\Event\BlockEvents;
use Supra\Controller\Pages\Event\BlockEventsArgs;
use Supra\Cache\CacheGroupManager;
use Supra\Controller\Exception\AuthorizationRequiredException;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;

/**
 * View controller
 * @method PageRequest getRequest()
 * @method Response\HttpResponse getResponse()
 */
class ViewController extends PageController
{

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		//TODO: create listener which would add each loaded entity as child of this
		return ObjectRepository::getEntityManager('#cms');
	}

	/**
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function prepare(RequestInterface $request, ResponseInterface $response)
	{
		// Downcast to local request object
		if ( ! $request instanceof namespace\Request\PageRequest) {
			$request = new namespace\Request\ViewRequest($request);
		}

		$request->setDoctrineEntityManager($this->getEntityManager());

		parent::prepare($request, $response);
	}

	/**
	 * Execute controller
	 */
	public function execute()
	{
		$request = $this->getRequest();

		$localization = $request->getPageLocalization();
		/* @var $localization Entity\PageLocalization */

		if ($localization instanceof Entity\TemplateLocalization) {
			
		} else if ($localization instanceof Entity\PageLocalization) {

			$isLimited = $localization->getPathEntity()
					->isLimited();

			if ($isLimited) {
				throw new AuthorizationRequiredException();
			}
		} else {

			$this->log->warn("Page received from ViewRequest is not of PageLocalization or TemplateLocalization instance, requested uri: ", $request->getActionString(), ', got ', (is_object($localization) ? get_class($localization) : gettype($localization)));
			throw new ResourceNotFoundException("Wrong localization instance received");
		}


		parent::execute();
	}

}
