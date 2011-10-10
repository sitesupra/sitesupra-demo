<?php

namespace Supra\Controller\Pages\Application;

use Supra\Controller\Pages\Entity;
use Supra\Uri\Path;
use Doctrine\ORM\EntityManager;

/**
 * Interface for page applications
 */
interface PageApplicationInterface
{
	const SITEMAP_VIEW_COLLAPSED = 'collapsed';
	const SITEMAP_VIEW_EXPANDED = 'expanded';
	
	/**
	 * Renerates the base path for page localization.
	 * Must NOT start and end with "/"
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization);
	
	/**
	 * Whether the application page has it's own path
	 * @return boolean
	 */
	public function hasPath();
	
	/**
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em);
	
	/**
	 * @param Entity\ApplicationLocalization $applicationLocalization
	 */
	public function setApplicationLocalization(Entity\ApplicationLocalization $applicationLocalization);

	/**
	 * @param boolean $show
	 */
	public function showInactivePages($show);
	
	/**
	 * Returns set having SITEMAP_VIEW_COLLAPSED and/or SITEMAP_VIEW_EXPANDED
	 * @return array
	 */
	public function getAvailableSitemapViewModes();
	
	/**
	 * Load collapsed sitemap view, return null if collapsing is not implemented
	 * @return array
	 */
	public function collapsedSitemapView();
	
	/**
	 * Load expanded sitemap view, can contain grouping
	 * @return array
	 */
	public function expandedSitemapView();
	
	/**
	 * Tells if the application node has hidden system pages (group page)
	 * @return boolean
	 */
	public function hasHiddenPages();
	
	/**
	 * Load hidden pages (usually array of GroupPage objects)
	 * @return array
	 */
	public function getHiddenPages();
}
