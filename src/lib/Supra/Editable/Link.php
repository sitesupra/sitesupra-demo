<?php

namespace Supra\Editable;

/**
 * Link
 * @TODO: what could be a default value for link?
 * @TODO: possibility to limit to file/image/internal/external links
 */
class Link extends EditableAbstraction
{
	const EDITOR_TYPE = 'Link';
	const EDITOR_INLINE_EDITABLE = false;
	
	private $groupsSelectable = false;
	private $managerMode = 'link';
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
	 * Whether to allow select virtual folders
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
