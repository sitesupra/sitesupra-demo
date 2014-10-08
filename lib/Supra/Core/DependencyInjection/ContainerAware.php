<?php

namespace Supra\Core\DependencyInjection;

interface ContainerAware
{
    public function setContainer(ContainerInterface $container);
}
