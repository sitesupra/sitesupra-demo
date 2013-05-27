<?php

namespace Project\FancyBlocks\BlogComments;

use Supra\Controller\Pages\BlockController;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Request\PageRequestEdit;

class BlogCommentsBlock extends BlockController
{

	protected function doExecute()
	{
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		
		$blogApplication = $this->getBlogApplication();
		if ( ! $blogApplication instanceof BlogApplication) {
			$response->outputTemplate('application-missing.html.twig');
			return null;
		}
		
		$request = $this->getRequest();
		/* @var $request \Supra\Controller\Pages\Request\PageRequest */
		if ($request->isPost() 
				&& $request->getQuery()->offsetExists('postComment')) {
			
			$this->handleCommentPostRequest();
		}
		
//		// @FIXME: remove comment action must request for blog app manager, not blocks
//		// current implementation does not checks user permissions
//		if ($request->isBlockRequest()) {
//			
//			$query = $request->getQuery();
//			
//			if ($query->has('comment_id') 
//					&& $query->get('action', null) == 'remove_comment') {
//			
//				$commentId = $query->get('comment_id');
//				$blogApplication->removeCommentById($commentId);
//			}
//		}
        	
		$postComments = array();
		
		$localization = $request->getPageLocalization();
		
		if ($localization instanceof Entity\PageLocalization) {
			$postComments = $blogApplication->getCommentsForLocalization($localization, $request instanceof PageRequestEdit);
		}
			
		$applicationLocalizationId = $blogApplication->getApplicationLocalization()->getId();
        
        $response->assign('comments', $postComments)
                ->assign('blogApplicationLocalizationId', $applicationLocalizationId)
                ->outputTemplate('index.html.twig');
	}
	
	/**
	 * @return \Supra\Controller\Pages\Blog\BlogApplication
	 */
	private function getBlogApplication()
	{
		$request = $this->getRequest();
		
		$parentPage = $request->getPageLocalization()
				->getMaster()
				->getParent();
		
		if ($parentPage instanceof Entity\ApplicationPage) {
			
			$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
			$localization = $parentPage->getLocalization($request->getLocale());
			
			$application = \Supra\Controller\Pages\Application\PageApplicationCollection::getInstance()
					->createApplication($localization, $em);
			
			return $application;
		}

		return null;
	}
	
	/**
	 * 
	 */
	protected function handleCommentPostRequest()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$post = $request->getPost();
		
		$errors = array();
		
		$name = $post->get('name', null);
		$email = $post->getValidOrNull('email', \Supra\Validator\Type\AbstractType::EMAIL);
		$comment = $post->get('comment', null);
		
		if (empty($name)) {
			$errors[] = 'Name is required';
		}
		
		if (empty($email)) {
			$errors[] = 'Valid email is required';
		}
		
		if (empty($comment)) {
			$errors[] = 'Comment is required';
		}
		
		if ( ! empty($errors)) {
			$this->getResponse()
					->assign('errors', $errors);
			
			return;
		}
		
		$website = $post->get('url', null);
		
		$blogApp = $this->getBlogApplication();
		
		$blogComment = $blogApp->createComment();
		
		$blogComment->setAuthorName($name);
		$blogComment->setAuthorEmail($email);
		$blogComment->setAuthorWebsite($website);
		
		// @TODO: more advanced validation?
		$comment = mb_substr($comment, 0, 1000);
		
		$blogComment->setComment($comment);
		
		$localization = $this->getRequest()
				->getPageLocalization();
		
		$blogComment->setPageLocalization($localization);
		
		$blogApp->storeComment($blogComment);
	}
	
}
