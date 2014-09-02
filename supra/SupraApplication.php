<?php

use Supra\Core\Supra;

class SupraApplication extends Supra
{
    protected function registerPackages()
    {
        return array(
            new \Supra\Packages\Cms\SupraPackageCms()
        );
    }
}