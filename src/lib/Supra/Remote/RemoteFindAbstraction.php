<?php

namespace Supra\Remote;

use Symfony\Component\Console\Command\Command;
use Supra\Log\Log;
use Supra\Console\Output\ArrayOutput;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Supra\Remote\Client\RemoteCommandService;
use Supra\Console\Output\CommandOutputWithData;
use Supra\User\Entity;
use SupraPortal\SiteUser\Entity\SiteUser;

class RemoteFindAbstraction extends Command
{

	/**
	 * @var array 
	 */
	public $output = array(
		'data' => null,
		'error' => null,
	);

	/**
	 * @var OutputInterface
	 */
	public $outputInstance;

	/**
	 * @var \Supra\User\RemoteUserProvider
	 */
	public $userProvider;

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	public $em;

	/**
	 * @var \Supra\Log\Writer\WriterAbstraction
	 */
	public $log;

	public function __construct($name = null)
	{
		parent::__construct($name);
		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->em = ObjectRepository::getEntityManager($this);
		$this->log = ObjectRepository::getLogger($this);
	}

	/**
	 * @param OutputInterface $this->outputInstance
	 * @param array $array 
	 */
	public function writeArrayToOutput($array, $depth = 1)
	{
		$tab = str_repeat("\t", $depth);
		if ($depth <= 1) {
			$this->outputInstance->writeln("\n{$tab}Results:\n");
		}

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->outputInstance->writeln("{$tab}[$key] => ");
				$this->writeArrayToOutput($value,  ++ $depth);
				-- $depth;
			} else {
				$this->outputInstance->writeln("{$tab}[$key] => \"$value\"");
			}
		}
		$this->outputInstance->writeln('');
	}

}

