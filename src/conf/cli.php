<?php

$cli = \Supra\Console\Application::getInstance();

$commands = array(
	'Supra\Remote\Command\RemoteFindUserCommand'
);

$cli->addCommandClasses($commands);