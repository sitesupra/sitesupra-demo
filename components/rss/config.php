<?php

$rssNamespace = new \Supra\Loader\NamespaceRecord('Project\\Rss', __DIR__);
\Supra\Loader\Registry::getInstance()->registerNamespace($rssNamespace);