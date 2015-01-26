<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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
            'fixedMaxCropHeight'    => isset($this->options['fixedMaxCropHeight']) ? $this->options['fixedMaxCropHeight'] : false,
            'allowCropZooming'      => isset($this->options['allowCropZooming']) ? $this->options['allowCropZooming'] : true,
        );
    }

}
