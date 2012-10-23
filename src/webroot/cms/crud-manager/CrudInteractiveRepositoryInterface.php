<?php

namespace Supra\Cms\CrudManager;

interface CrudInteractiveRepositoryInterface
{
    /**
     * Returns an array of events this repository listens.
     *
     * @return array
     */
    function getSubscribedEvents();
}