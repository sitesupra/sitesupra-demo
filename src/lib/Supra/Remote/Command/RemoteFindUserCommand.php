<?php

namespace Supra\Remote\Command;

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

class RemoteFindUserCommand extends Command
{

	/**
	 * @var array 
	 */
	private $output = array(
		'user' => null,
		'error' => null,
	);

	protected function configure()
	{
		$this->setName('su:remote:find_user')
				->setDescription('Remote client to search for user in supra instance.')
				->setDefinition(new InputDefinition(array(
							new InputArgument('field', InputArgument::REQUIRED, 'Field to search for. One of \'id\', \'email\', \'login\', \'name\''),
							new InputArgument('value', InputArgument::REQUIRED),
						)));
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$userProvider = ObjectRepository::getUserProvider($this);
		$criteria = array();

		$field = $input->getArgument('field');

		$allowedFields = array('id', 'email', 'login', 'name');
		if ( ! in_array($field, $allowedFields, true)) {
			$message = "Field {$field} was not found in allowed field list. Allowed fields: " . join(', ', $allowedFields) . '.';
			$output->writeln($message);
			if ($output instanceof CommandOutputWithData) {
				$this->output['error'] = $message;
				$output->setData($this->output);
			}

			return;
		}

		$value = $input->getArgument('value');

		$method = 'findUserBy' . ucfirst($field);
		$user = $userProvider->$method($value);

		if (empty($user)) {
			$message = 'There is no any user with such details';
			$output->writeln($message);
			if ($output instanceof CommandOutputWithData) {
				$this->output['error'] = $message;
				$output->setData($this->output);
			}

			return;
		}

		if ($output instanceof CommandOutputWithData) {
			$this->output['user'] = $user;
			$user->getGroup()->getId();
			$output->setData($this->output);

			return;
		}

		if ($user instanceof \Supra\User\Entity\User) {
			$userData = array(
				'id' => $user->getId(),
				'email' => $user->getEmail(),
				'name' => $user->getName(),
				'login' => $user->getLastLoginTime()->format('d.m.Y H:i:s'),
				'created_at' => $user->getCreationTime()->format('d.m.Y H:i:s'),
				'modified_at' => $user->getModificationTime()->format('d.m.Y H:i:s'),
			);

			$this->writeArrayToOutput($output, $userData);

			return;
		}

		if (is_array($user)) {
			$this->writeArrayToOutput($output, $user);

			return;
		}
	}

	/**
	 * @param OutputInterface $output
	 * @param array $array 
	 */
	private function writeArrayToOutput(OutputInterface $output, $array)
	{
		$output->writeln("\n\tUser is found:\n");
		foreach ($array as $key => $value) {
			$output->writeln("\t[$key] => \"$value\"");
		}
		$output->writeln('');
	}

}
