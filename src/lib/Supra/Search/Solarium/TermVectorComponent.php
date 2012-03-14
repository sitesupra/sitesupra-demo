<?php

namespace Supra\Search\Solarium;

class TermVectorComponent
{

    const TYPE = 'tvComponent';

    public function getType()
    {
        return self::TYPE;
    }

    static function CN()
    {
        return get_called_class();
    }

}

