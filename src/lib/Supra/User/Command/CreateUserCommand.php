<?php

namespace Supra\User\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\UserProvider;
use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;

require_once SUPRA_CONF_PATH . 'user.php';

/**
 * CreateUserCommand
 *
 * @author Dmitry Polovka <dmitry.polovka@videinfra.com>
 */
class CreateUserCommand extends Command
{

	/**
	 * Adds an option.
	 *
	 * @param string  $name        The option name
	 * @param string  $shortcut    The shortcut (can be null)
	 * @param integer $mode        The option mode: One of the InputOption::VALUE_* constants
	 * @param string  $description A description text
	 * @param mixed   $default     The default value (must be null for InputOption::VALUE_REQUIRED or self::VALUE_NONE)
	 *
	 * @return Command The current instance 
	 */
	protected function configure()
	{
		$this->setName('su:user:create_user')
				->setDescription('Creates new user')
				->setHelp('Creates new user')
				->addOption('email', null, Console\Input\InputOption::VALUE_REQUIRED, 'User email. Confirmation link will be sent to that email')
				->addOption('group', null, Console\Input\InputOption::VALUE_OPTIONAL, 'User group. Can be one of admins, contribs or supers.', 'admins')
				->addOption('name', null, Console\Input\InputOption::VALUE_OPTIONAL, 'User first name and last name');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		//The command will create new admin account with the email specified in the arguments. Name might be passed as well. Use email first part if omitted.
		//Password creation link must be sent by mail.
		//User groups must be created beforehand if missing.
		//The command must be inside src/lb not tests.

		$email = $input->getOption('email');
		if (is_null($email)) {
			throw new RuntimeException('Email is required parameter');
		}

		$name = $input->getOption('name');
		if (is_null($name)) {
			$name = strstr($email, '@', true);
		}

		//TODO: implement normal group loader and IDs
		$dummyGroupMap = array('admins', 'contribs', 'supers');

		$groupName = trim(strtolower($input->getOption('group')));

		if ( ! in_array($groupName, $dummyGroupMap)) {
			$groupName = 'admins';
		}

		$userProvider = ObjectRepository::getUserProvider($this);
		if ( ! $userProvider instanceof UserProvider) {
			throw new RuntimeException('Internal error: Could not reach user provider');
		}

		$group = $userProvider->findGroupByName($groupName);

		$user = $userProvider->createUser();

		// TODO: add avatar
		$user->setName($name);
		$user->setEmail($email);

		$user->setGroup($group);

		$userProvider->validate($user);

		$authAdapter = $userProvider->getAuthAdapter();
		$authAdapter->credentialChange($user);
		
		$output->writeln('Added user "' . $name . '" to "' . $groupName . '" group');
		
		$userAction = new InternalUserManagerAbstractAction();
		ObjectRepository::setCallerParent($userAction, $this, true);
		
		$userAction->sendPasswordChangeLink($user, 'createpassword');
	}

}