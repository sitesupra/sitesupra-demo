<?php

namespace Sample\Fixtures;

use Sp\FixtureDumper\Converter\Handler\DateHandler as BaseDateHandler;
use Sp\FixtureDumper\Converter\VisitorInterface;

class DateHandler extends BaseDateHandler
{
    public function convertToYml(VisitorInterface $visitor, \DateTime $data)
    {
        return sprintf('<(new \DateTime(\'%s\'))>', $data->format('c'));
    }
}