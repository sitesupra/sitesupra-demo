<?php

namespace Supra\Search\Solarium;

use \Solarium_Client_RequestBuilder;
use \Solarium_Client_Request;

class TermVectorComponentRequestBuilder extends Solarium_Client_RequestBuilder
{

    public function build($component, Solarium_Client_Request $request)
    {
        $request->addParam('qt', 'tvrh');
        $request->addParam('tv', true);
        $request->addParam('tv.tf', true);
                
        return $request;
    }

    public static function CN()
    {
        return get_called_class();
    }

}