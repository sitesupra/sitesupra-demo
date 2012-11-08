<?php

namespace Supra\AuditLog\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Supra\ObjectRepository\ObjectRepository;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Exception;

class CleanAuditLogCommand extends Command
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var string
	 */
	protected $userLogin;

	/**
	 * @var boolean 
	 */
	protected $force;

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 */
	protected function configure()
	{
		$this->setName('su:audit:clean_history')
				->setDescription('Clean history (audit log) of page(s) for user(s)')
				->addOption('force', null, InputOption::VALUE_NONE, 'Actually do the cleaning')
				->addOption('user', null, InputOption::VALUE_REQUIRED, 'User login. If not specified, all records will be affected', null);
	}

	/**
	 * @param InputInterface $input
	 */
	protected function readParameters(InputInterface $input)
	{
		$this->userLogin = $input->getOption('user', null);
		$this->force = $input->getOption('force', false);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->readParameters($input);

		$dbConnection = $this->getEntityManager()->getConnection();

		$whereParts = array('1 = 1');
		$binds = array();

		if ( ! empty($this->userLogin)) {
			$whereParts[] = 'user = :user';
			$userLogin = $this->userLogin;
			$binds[] = function($statement) use ($userLogin) {
						$statement->bindValue('user', $userLogin);
					};
		}

		$affectedEntryCountSql = 'SELECT COUNT(*) AS affected_entries FROM su_AuditLog WHERE ' . join(' AND ', $whereParts);
		$statement = $dbConnection->prepare($affectedEntryCountSql);
		foreach ($binds as $bind) {
			$bind($statement);
		}

		$statement->execute();
		
		$affectedEntryCount = $statement->fetchColumn(0);

		if ($this->force) {

			$output->writeln('Will delete ' . $affectedEntryCount . ' row(s).');

			$deleteEntriesSql = 'DELETE FROM su_AuditLog WHERE ' . join(' AND ', $whereParts);
			$statement = $dbConnection->prepare($deleteEntriesSql);
			foreach ($binds as $bind) {
				$bind($statement);
			}

			$statement->execute();

			$output->writeln('Done.');
		} else {

			$output->writeln('Would delete ' . $affectedEntryCount . ' row(s).');
		}
	}

}
