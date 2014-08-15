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
		$info = \Supra\ObjectRepository\ObjectRepository::getSystemInfo($this);
		/* @var $info \SupraSite\SiteInfo\Info */

		if ($info instanceof \SupraSite\SiteInfo\Info && $info->isDemo()) {
			return;
		}

		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$post = $request->getPost();

		$errors = array();

		$name = trim($post->get('name', ''));
		$email = $post->getValidOrNull('email', \Supra\Validator\Type\AbstractType::EMAIL);
		$comment = trim($post->get('comment', ''));
		$website = trim($post->get('url', ''));

		if (empty($name)) {
			$errors['name'] = 'Name is required';
		}

		if (empty($email)) {
			$errors['email'] = 'Valid email is required';
		}

		if (empty($comment)) {
			$errors['comment'] = 'Comment is required';
		}

		if ( ! empty($errors)) {
			$this->getResponse()
					->assign('errors', $errors)
					->assign('values', array(
						'name' => $name,
						'url' => $website,
						'email' => $email,
						'comment' => $comment,
					));

			return;
		}

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
		/* @var $localization Entity\PageLocalization */

		$blogComment->setPageLocalization($localization);

		$blogApp->storeComment($blogComment);

		// Redirect to self
		// @TODO: should we show 'comment successfully added' message?
		$selfPath = $localization->getFullPath(\Supra\Uri\Path::FORMAT_LEFT_DELIMITER);

		$this->getResponse()
				->redirect($selfPath);

		throw new \Supra\Controller\Exception\StopRequestException;
	}

}
