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

namespace Supra\Package\Cms\Entity\ReferencedElement;

/**
 * @Entity
 */
class MediaReferencedElement extends ReferencedElementAbstract
{
    const TYPE_ID = 'media';

    /**
     * Media URL
     *
     * @Column(type="text")
     * @var string
     */
    protected $url;

    /**
     * @Column(type="integer", nullable=true)
     * @var int
     */
    protected $width;

    /**
     * @Column(type="integer", nullable=true)
     * @var int
     */
    protected $height;

    /**
     * Sets media url.
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array(
            'type'      => self::TYPE_ID,
            'url'       => $this->url,
            'width'     => $this->width,
            'height'    => $this->height,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function fillFromArray(array $array)
    {
        $this->url = ! empty($array['url']) ? $array['url'] : null;
        $this->width = ! empty($array['width']) ? $array['width'] : null;
        $this->height = ! empty($array['height']) ? $array['height'] : null;
    }
}