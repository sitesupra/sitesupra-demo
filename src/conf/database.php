<?php

use Supra\Database\Configuration;

$public = new Configuration\PublicEntityManagerConfiguration();
$public->configure();

$draft = new Configuration\DraftEntityManagerConfiguration();
$draft->configure();

$audit = new Configuration\AuditEntityManagerConfiguration();
$audit->configure();
