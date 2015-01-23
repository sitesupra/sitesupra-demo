<?php

namespace Supra\Package\Cms\Editable;

class InlineMedia extends Editable
{
    /**
     * @return string
     */
    public function getEditorType()
    {
        return 'InlineMedia';
    }

    /**
     * @return array
     */
    public function getAdditionalParameters()
    {
        return array(
            'allowVideo'    => isset($this->options['allowVideo']) ? $this->options['allowVideo'] : true,
            'allowImage'    => isset($this->options['allowImage']) ? $this->options['allowImage'] : true,
            'fixedMaxCropWidth'     => isset($this->options['fixedMaxCropWidth']) ? $this->options['fixedMaxCropWidth'] : true,
            'fixedMaxCropHeight'    => isset($this->options['fixedMaxCropHeight']) ? $this->options['fixedMaxCropHeight'] : true,
            'allowCropZooming'      => isset($this->options['allowCropZooming']) ? $this->options['allowCropZooming'] : true,
        );
    }

}
