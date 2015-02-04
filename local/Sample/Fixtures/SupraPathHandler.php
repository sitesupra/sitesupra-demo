<?php

namespace Sample\Fixtures;

use Sp\FixtureDumper\Converter\Handler\HandlerSubscriberInterface;
use Sp\FixtureDumper\Converter\VisitorInterface;
use Supra\Package\Cms\Uri\Path;

class SupraPathHandler implements HandlerSubscriberInterface
{

    /**
     * {@inheritdoc}
     */
    public function getSubscribedMethods()
    {
        return array(array(
            'format' => 'yml',
            'type' => 'Supra\Package\Cms\Uri\Path',
            'method' => 'pathToString'
        ));
    }

    public function pathToString(VisitorInterface $visitor, Path $path)
    {
        return $path->getFullPath(Path::FORMAT_LEFT_DELIMITER);
    }

}
