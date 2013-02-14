<?php

namespace Supra\Editable;

/**
 * String editable content
 */
class InlineMap extends EditableAbstraction
{
    const EDITOR_TYPE = 'InlineMap';
    const EDITOR_INLINE_EDITABLE = true;
    
    /**
     * Return editor type
     * @return string
     */
    public function getEditorType()
    {
        return static::EDITOR_TYPE;
    }
    
    /**
     * {@inheritdoc}
     * @return boolean
     */
    public function isInlineEditable()
    {
        return static::EDITOR_INLINE_EDITABLE;
    }
    
}
