<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageRevisionData;
use Supra\Controller\Pages\Entity\Page;
use Supra\User\Entity\User;
use Supra\Controller\Pages\Exception;

class CleanHistoryCommand extends Command
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var string
	 */
	protected $pageId;

	/**
	 * @var string
	 */
	protected $userId;

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
		$this->setName('su:pages:clean_history')
				->setDescription('Clean history (audit log) of page(s) for user(s)')
				->addOption('force', null, InputOption::VALUE_NONE, 'Actually do the cleaning')
				->addOption('page', null, InputOption::VALUE_REQUIRED, 'Page Id, if not specified, all pages will be affected', null)
				->addOption('user', null, InputOption::VALUE_REQUIRED, 'User Id, if not specified, all users will be affected', null);
	}

	/**
	 * @param InputInterface $input
	 */
	protected function readParameters(InputInterface $input)
	{
		$this->pageId = $input->getOption('page', null);
		$this->userId = $input->getOption('user', null);
		$this->force = $input->getOption('force', false);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->readParameters($input);

		$em = $this->getEntityManager();

		//$pageRevisionDataRepositroy = $em->getClassMetadata(PageRevisionData::CN());

		$qb = $em->createQueryBuilder();

		$qb->from(PageRevisionData::CN(), 'h');

		if ( ! empty($this->userId)) {

			$userRepository = $em->getRepository(User::CN());

			$user = $userRepository->find($this->userId);

			if (empty($user)) {
				throw new Exception\RuntimeException('User not found.');
			}

			$qb->andWhere('h.user.id = :userId');
			$qb->setParameter('user_id', $this->userId);
		}

		if ( ! empty($this->pageId)) {

			$pageRepository = $em->getRepository(Page::CN());

			/* @var $page Page */
			$page = $pageRepository->find($this->pageId);

			if ( ! empty($page)) {

				$references = array($this->pageId);

				$pageLocalizations = $page->getLocalizations();
				foreach ($pageLocalizations as $localization) {
					$references[] = $localization->getId();
				}

				$qb->andWhere('h.reference IN :references');
				$qb->setParameter('references', $references);
			} else {
				throw new Exception\RuntimeException('Page not found.');
			}
		}

		$qb->select('count(h.id)');
		$query = $qb->getQuery();
		$query->execute();
		$pageRevisionDataRowCount = $query->getSingleScalarResult();

		if ($this->force) {

			$output->writeln('Will remove ' . $pageRevisionDataRowCount . ' row(s).');

			$qb->resetDQLPart('select');
			$qb->delete();
			$query = $qb->getQuery();
			$query->execute();

			$output->writeln('Done.');
		} else {

			$output->writeln('Would remove ' . $pageRevisionDataRowCount . ' row(s). Use --force to actually remove them.');
		}
	}

}
