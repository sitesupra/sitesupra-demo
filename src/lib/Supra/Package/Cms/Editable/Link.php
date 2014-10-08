<?php

namespace Supra\Package\Cms\Editable;

/**
 * Link Editable
 *
 * $managerMode 
 *
 * @TODO: what could be a default value for link?
 */
class Link extends EditableAbstraction
{
	const EDITOR_TYPE = 'Link';
	const EDITOR_INLINE_EDITABLE = false;

	const MANAGER_MODE_LINK = 'link';
	const MANAGER_MODE_PAGE = 'page';
	const MANAGER_MODE_IMAGE = 'image';

	/**
	 * @var bool
	 */
	private $groupsSelectable = false;

	/**
	 * Link manager mode
	 * Accepts the following values:
	 *	 'link' - allows to choose page, image or file
	 *	 'page' - allows to choose page
	 *	 'image' - allows to choose only images
	 *
	 * @var string
	 */
	private $managerMode = 'link';

	/**
	 * @var bool
	 */
	private $labelSet = false;
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return self::EDITOR_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 * @return boolean
	 */
	public function isInlineEditable()
	{
		return self::EDITOR_INLINE_EDITABLE;
	}
	
	/**
	 * Sets whether the virtual groups can be selected or not
	 *
	 * @param boolean $groupsSelectable
	 */
	public function setGroupsSelectable($groupsSelectable)
	{
		$this->groupsSelectable = $groupsSelectable;
	}
	
	public function setManagerMode($mode)
	{
		$this->managerMode = $mode;
	}
    
    public function setLabelSet($labelSet)
    {
        $this->labelSet = $labelSet;
    }
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'groupsSelectable' => $this->groupsSelectable,
			'mode' => $this->managerMode,
            'labelSet' => $this->labelSet,
		);
	}
}
