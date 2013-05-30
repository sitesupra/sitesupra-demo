<?php

namespace Supra\Cms\BlogManager\Comments;

/**
 * Comments management action controller class
 */
class CommentsAction extends \Supra\Cms\BlogManager\BlogManagerAbstractAction
{
	/**
	 * Comment delete action
	 */
	public function deleteAction()
	{
		$this->isPostRequest();    
		$this->checkEditPermissions();
		
		$id = $this->getRequestParameter('id');
		
		$blogApplication = $this->getBlogApplication();
		$comment = $blogApplication->findCommentById($id);
		
		if (empty($comment)) {
			throw new \Supra\Cms\Exception\CmsException("Comment #{$id} not found");
		}
		
		$em = $blogApplication->getEntityManager();
		
		$em->remove($comment);
		$em->flush();
	}
	
	/**
	 * Comment approve action
	 */
	public function approveAction()
	{
		$this->changeCommentApproveStatus(true);
	}
	
	/**
	 * Comment un-approve action
	 */
	public function unapproveAction()
	{
		$this->changeCommentApproveStatus(false);
	}
	
	/**
	 * @param boolean $status
	 * @throws \Supra\Cms\Exception\CmsException
	 */
	protected function changeCommentApproveStatus($status)
	{
		$this->isPostRequest();
		$this->checkEditPermissions();
		
		$id = $this->getRequestParameter('id');
		$blogApplication = $this->getBlogApplication();
		
		$comment = $blogApplication->findCommentById($id);
		if (empty($comment)) {
			throw new \Supra\Cms\Exception\CmsException("Comment #{$id} not found");
		}
		
		$comment->setApproved($status);
		
		$blogApplication->getEntityManager()
				->flush();
	}
}