<?php

namespace Supra\Cms\BlogManager\Settings;

/**
 * Blog application settings action controller class
 */
class SettingsAction extends \Supra\Cms\BlogManager\BlogManagerAbstractAction
{
	/**
	 *
	 */
	public function loadAction()
	{
		$blogApplication = $this->getBlogApplication();
		
		$responseData = array(
			'authors' => array(),
			'tags' => array(),
			'templates' => array(
				'post_template' => $blogApplication->getPostDefaultTemplateId(),
			),
			'comments' => array(
				'moderation_enabled' => $blogApplication->isCommentModerationEnabled(),
			),
		);
		
		$users = $blogApplication->getAllBlogApplicationUsers();
		/* @var $users \Supra\Controller\Pages\Entity\Blog\BlogApplicationUser[] */

		foreach ($users as $user) {
			$responseData['authors'][] = array(
				'id' => $user->getSupraUserId(),
				'name' => $user->getName(),
				'avatar' => $user->getAvatar(),
				'about' => $user->getAboutText(),
			);
		}
		
		$responseData['tags'] = $blogApplication->getAllTagsArray();
			
		$this->getResponse()
				->setResponseData($responseData);
	}
		
	/**
	 *
	 */
	public function deleteTagAction()
	{
		$this->isPostRequest();
		$this->checkEditPermissions();
		
		$tagName = $this->getRequestParameter('name');
		
		if ( ! empty($tagName)) {
			$blogApplication = $this->getBlogApplication();
			$blogApplication->deleteTagByName($tagName);
		}
		
	    $this->getResponse()
                ->setResponseData(true);
	}
    
    /**
     *
     */
    public function saveTemplatesAction()
    {
		$this->isPostRequest();
		$this->checkEditPermissions();
		
		$templateId = $this->getRequestParameter('template');
		if ( ! empty($templateId)) {
			$this->getBlogApplication()
					->setPostDefaultTemplateId($templateId);
		}
		
        $this->getResponse()
                ->setResponseData(true);
    }

	/**
	 *
	 */
	public function saveAuthorsAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();
		
		$blogApplication = $this->getBlogApplication();

		$userId = $input->get('id');
		$blogUser = $blogApplication->findUserBySupraUserId($userId);
			
		if ($blogUser !== null) {
			$blogUser->setName($input->get('name'));
			$blogUser->setAboutText($input->get('about'));
			
//			// @TODO: avatar is not editable now
//			$author->setAvatar($input->get('avatar'));
			
			$em = $blogApplication->getEntityManager();
			$em->merge($blogUser);
			$em->flush();
		}
		
		$this->getResponse()
				->setResponseData(true);
	}
	
	/**
	 * 
	 */
	public function saveCommentsAction()
	{
		$this->isPostRequest();
		
		$isModerationEnabled = $this->getRequestInput()
				->getValid('moderation_enabled', \Supra\Validator\Type\AbstractType::BOOLEAN);
		
		$blogApplication = $this->getBlogApplication();
		$blogApplication->setCommentModerationEnabled($isModerationEnabled);
	}
}