<?php

namespace Supra\Package\Cms\Pages\Layout\Theme;

class Layout implements ThemeLayoutInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var string
     */
    protected $icon;

    /**
     * @param string $name
     * @param string $title
     * @param string $fileName
     */
    public function __construct($name, $title, $fileName)
    {
        $this->name = $name;
        $this->title = $title;
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }
}