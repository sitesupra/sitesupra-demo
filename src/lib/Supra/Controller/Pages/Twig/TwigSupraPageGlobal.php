<?php

namespace Supra\Controller\Pages\Twig;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Request\RequestInterface;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Uri\Path;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Entity\Abstraction\Localization;
use Supra\Response\ResponseContext;
use Supra\Html\HtmlTag;
use Supra\Controller\Layout\Theme\ThemeInterface;

/**
 * Helper object for twig processor
 */
class TwigSupraPageGlobal extends TwigSupraGlobal
{

	/**
	 * @var array
	 */
	protected $currentThemeParameterValues;

	/**
	 * @var ThemeInterface
	 */
	protected $theme;

	/**
	 * Returns if in CMS mode
	 * @return boolean
	 */
	public function isCmsRequest()
	{
		return ($this->request instanceof PageRequestEdit);
	}

	/**
	 * Whether the passed link is actual - is some descendant opened currently
	 * @param string $path
	 * @param boolean $strict
	 * @return boolean
	 */
	public function isActive($path, $strict = false)
	{
		// Check if path is relative
		$pathData = parse_url($path);
		if ( ! empty($pathData['scheme'])
				|| ! empty($pathData['host'])
				|| ! empty($pathData['port'])
				|| ! empty($pathData['user'])
				|| ! empty($pathData['pass'])
		) {
			return false;
		}

		$path = $pathData['path'];

		$localization = $this->getLocalization();

		if ( ! $localization instanceof PageLocalization) {
			return false;
		}

		// Remove locale prefix
		$localeId = $localization->getLocale();
		$localeIdQuoted = preg_quote($localeId);
		$path = preg_replace('#^(/?)' . $localeIdQuoted . '(/|$)#', '$1', $path);

		$checkPath = new Path($path);
		$currentPath = $localization->getPath();

		if (is_null($currentPath)) {
			return false;
		}

		if ($strict) {
			if ($checkPath->equals($currentPath)) {
				return true;
			}
		} elseif ($currentPath->startsWith($checkPath)) {
			return true;
		}

		return false;
	}

	/**
	 * @return Localization
	 */
	public function getLocalization()
	{
		$request = $this->request;

		if ( ! $request instanceof PageRequest) {
			return;
		}

		$localization = $request->getPageLocalization();

		if ( ! $localization instanceof Localization) {
			return;
		}

		return $localization;
	}

	/**
	 * Generates page title tag with class name CMS would recognize
	 * @param string $tagName
	 * @return HtmlTag
	 */
	public function pageTitleHtmlTag($tagName = 'span')
	{
		$localization = $this->getLocalization();

		if (is_null($localization)) {
			return;
		}

		$title = $localization->getTitle();
		$htmlTag = new HtmlTag($tagName, $title);

		if ($this->isCmsRequest()) {
			$htmlTag->addClass('su-settings-title');
		}

		return $htmlTag;
	}

	/**
	 * @return array
	 */
	public function getTheme()
	{
		if (is_null($this->theme)) {
			throw new \Supra\Controller\Pages\Exception\RuntimeException("Theme is not set for the twig supra global but theme parameter is requested");
		}

		if (is_null($this->currentThemeParameterValues)) {
			$this->currentThemeParameterValues = $this->theme->getCurrentParameterSetOutputValues();
		}

		return $this->currentThemeParameterValues;
	}

	/**
	 * @param ThemeInterface $theme 
	 */
	public function setTheme(ThemeInterface $theme)
	{
		$this->theme = $theme;
	}

}
