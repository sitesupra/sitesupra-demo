<?php

// Register namespace
$rssNamespace = new Supra\Loader\NamespaceRecord('Project\\Text', __DIR__);
Supra\Loader\Registry::getInstance()->registerNamespace($rssNamespace);
