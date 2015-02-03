<?php

namespace Sample\Fixtures;

use Sp\FixtureDumper\Converter\Alice\YamlVisitor as BaseYamlVisitor;

class YamlVisitor extends BaseYamlVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visitBoolean($boolean)
    {
        return $boolean ? true : false;
    }
}