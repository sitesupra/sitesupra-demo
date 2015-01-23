<?php

namespace Supra\Package\Cms\Editable;

class Video extends Editable
{
    /**
     * @return string
     */
    public function getEditorType()
    {
        return 'Video';
    }

    /**
     * @return array
     */
    public function getAdditionalParameters()
    {
        return array(
            'allowSizeControls' => isset($this->options['allowSizeControls'])
                ? (bool) $this->options['allowSizeControls'] : true,
        );
    }
}
