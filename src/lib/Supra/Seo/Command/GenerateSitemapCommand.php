<?php

namespace Supra\Seo\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity;
use Supra\Exception\FilesystemPermissionException;

class GenerateSitemapCommand extends Command
{

	private $host;
	private $notIncludedInSearch = array();

	protected function configure()
	{
		$this->setName('su:seo:generate_sitemap')
				->setDescription('Generates sitemap.xml and robots.txt.')
				->setHelp('Generates sitemap.xml and robots.txt.
					Includes only records which are included in search and visible in sitemap');
	}

	/**
	 */
	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$systemInfo = ObjectRepository::getSystemInfo($this);
		$this->host = trim($systemInfo->hostName, '/');
		if (strpos($this->host, 'http') !== 0) {
			// @todo @fixme: hardcoded http protocol. Add https check.
			$this->host = 'http://' . $this->host;
		}

		$records = $this->prepareSitemap($output);

		$this->generateSitemapXml($records);
		$this->generateRobotsTxt();

		$output->writeln('Generated sitemap.xml and robots.txt in webroot');
	}

	/**
	 * @param Console\Output\OutputInterface $output
	 * @return array 
	 */
	private function prepareSitemap(Console\Output\OutputInterface $output)
	{
		$em = ObjectRepository::getEntityManager($this);
		$pageRepo = $em->getRepository(Entity\Page::CN());

		$rootPages = $pageRepo->getRootNodes();
		$rootPage = $rootPages[0];

		if ( ! $rootPage instanceof Entity\Page) {
			$output->writeln('No root page found in sitemap');
			return;
		}

		$localizations = array();

		// Manually creating getDescendants request with 5 levels
		$nestedSetNode = $rootPage->getNestedSetNode();

		$nestedSetRepository = $nestedSetNode->getRepository();
		/* @var $nestedSetRepository \Supra\NestedSet\DoctrineRepository */

		$searchCondition = $nestedSetRepository->createSearchCondition();
		$searchCondition->leftMoreThan($rootPage->getLeftValue());
		$searchCondition->leftLessThan($rootPage->getRightValue());
		$searchCondition->levelLessThanOrEqualsTo($rootPage->getLevel() + 10);

		$orderCondition = $nestedSetRepository->createSelectOrderRule();
		$orderCondition->byLeftAscending();

		$qb = $nestedSetRepository->createSearchQueryBuilder($searchCondition, $orderCondition);
		/* @var $qb \Doctrine\ORM\QueryBuilder */

		// This loads all current locale localizations and masters with one query
		$qb->from(Entity\PageLocalization::CN(), 'l');
		$qb->andWhere('l.master = e');

		// Need to include "e" as well so it isn't requested by separate query
		$qb->select('l, e');
		$qb->andWhere('l.active = true');
		$qb->join('l.path', 'p');
		$qb->andWhere('p.path IS NOT NULL');
		$qb->andWhere('l.redirect IS NULL');

		$result = $qb->getQuery()->getResult();

		$records = array();
		$revisions = array();
		// Filter out localizations only
		foreach ($result as $record) {
			if ( ! $record instanceof Entity\PageLocalization) {
				continue;
			}

			$locale = $record->getLocale();

			if ( ! $record->isIncludedInSearch()) {
				$this->notIncludedInSearch[$record->getId()] = '/' . $locale . '/' . $record->getPath()->getFullPath('/');
				continue;
			}

			$revisions[] = $record->getRevisionId();

			$records[$record->getId()] = array(
				'loc' => $this->host . '/' . $locale . '/' . $record->getPath()->getFullPath('/'),
				'lastmod' => date('Y-m-d', $record->getCreationTime()->getTimestamp()),
				'changefreq' => $record->getChangeFrequency(),
				'priority' => $record->getPagePriority(),
			);
		}

		$qb = $em->createQueryBuilder();
		$qb->from(Entity\PageRevisionData::CN(), 'r');
		$qb->select('r');
		$qb->where('r.id IN (?0)')
				->setParameter(0, $revisions);
		$result = $qb->getQuery();
		$dql = $result->getDQL();
		$result = $result->getResult();

		foreach ($result as $revision) {
			/* @var $revision Entity\PageRevisionData */
			$pageId = $revision->getReferenceId();
			if ( ! isset($records[$pageId])) {
				continue;
			}

			$records[$pageId]['lastmod'] = date('Y-m-d', $revision->getCreationTime()->getTimestamp());
		}

		return $records;
	}

	/**
	 * Generates sitemap and stores to webroot folder
	 * @param array $records
	 * @throws FilesystemPermissionException
	 */
	private function generateSitemapXml($records = array())
	{
		$xmlContent = '<?xml version="1.0" encoding="utf-8"?> 
				<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';

		$xml = new \SimpleXMLElement($xmlContent);

		foreach ($records as $record) {
			$subnode = $xml->addChild("url");
			foreach ($record as $key => $value) {
				if ($value != '') {
					$subnode->addChild("$key", "$value");
				}
			}
		}

		$xmlData = $xml->asXML(SUPRA_WEBROOT_PATH . 'sitemap.xml');
		if ( ! $xmlData) {
			throw new FilesystemPermissionException('Failed to create/overwrite sitemap.xml in ' . SUPRA_WEBROOT_PATH);
		}
	}

	private function generateRobotsTxt()
	{
		$path = SUPRA_WEBROOT_PATH . 'robots.txt';
		
		$content = 'User-agent: *' . PHP_EOL;

		foreach ($this->notIncludedInSearch as $record) {
			$content .= "Disallow: {$record}$" . PHP_EOL;
		}
		
		$content .= "Sitemap: {$this->host}/sitemap.xml" . PHP_EOL;

		$fp = fopen($path, 'w');

		if ( ! fwrite($fp, $content)) {
			throw new FilesystemPermissionException('Failed to write into robots.txt');
		}

		fclose($fp);
	}

}