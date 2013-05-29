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
			     'template' => '',
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
	    $this->getResponse()
                ->setResponseData(true);
	}
    
    /**
     *
     */
    public function saveTemplatesAction()
    {
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
		$knownAuthors = $blogApplication->getAllBlogApplicationUsers();
		
		while($input->valid()) {
			$authorInput = $input->getNext();
			
			$id = $authorInput->get('id');
			
			if (isset($knownAuthors[$id])) {
				$author = $knownAuthors[$id];
				
				$author->setName($authorInput->get('name'));
				$author->setAvatar($authorInput->get('avatar'));
				$author->setAboutText($authorInput->get('about'));
			}
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