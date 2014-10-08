<?php

namespace Supra\Package\Cms\Editable;

/**
 * Tree
 */
class Tree extends EditableAbstraction
{
	const EDITOR_TYPE = 'Tree';
	
	/**
	 * @var boolean
	 */
	private $groupsSelectable = false;

	/**
	 * @var boolean
	 */
	private $labelSet = false;

	/**
	 * @var string
	 */
	private $sourceId = '';
	
	/**
	 * Return editor type
	 * @return string
	 */
	public function getEditorType()
	{
		return static::EDITOR_TYPE;
	}
	
	/**
	 * Whether to allow select virtual folders
	 * @param boolean $groupsSelectable
	 */
	public function setGroupsSelectable($groupsSelectable)
	{
		$this->groupsSelectable = $groupsSelectable;
	}

	/**
	 * @param boolean $labelSet
	 */
    public function setLabelSet($labelSet)
    {
        $this->labelSet = $labelSet;
    }

	/**
	 * @param string $sourceId
	 */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    }
	
	/**
	 * @return array
	 */
	public function getAdditionalParameters()
	{
		return array(
			'groupsSelectable' => $this->groupsSelectable,
            'labelSet' => $this->labelSet,
            'sourceId' => $this->sourceId,
		);
	}

	public function isInlineEditable()
	{
		return false;
	}
}
