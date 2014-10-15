<?php

namespace Supra\Package\Cms\Pages\Markup\Abstraction;

abstract class ContentElement extends ElementAbstraction
{

    /**
     * @var string
     */
    protected $content;

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getSafeContent()
    {
        $from = array('&nbsp;', '&nbsp');
        $to = array(' ', ' ');

        return str_replace($from, $to, $this->content);
    }

    /**
     * @param string $content 
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

}
