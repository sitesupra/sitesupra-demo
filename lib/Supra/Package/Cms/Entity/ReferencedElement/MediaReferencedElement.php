<?php

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
     * @Column(type="string", nullable=true)
     * @var string
     */
    protected $align;

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
     * @param string $align
     */
    public function setAlign($align)
    {
        $this->align = $align;
    }

    /**
     * @return string
     */
    public function getAlign()
    {
        return $this->align;
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
            'align'     => $this->align,
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
        $this->align = ! empty($array['align']) ? $array['align'] : null;
    }
}