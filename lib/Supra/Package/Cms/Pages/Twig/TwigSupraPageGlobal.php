<?php

namespace Supra\Package\Cms\Pages\Twig;

use Supra\Package\Cms\Pages\Request\PageRequestEdit;
use Supra\Package\Cms\Pages\Request\PageRequest;
use Supra\Package\Cms\Pages\Response\ResponseContext;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Uri\Path;
use Supra\Package\Cms\Html\HtmlTag;

/**
 * Helper object for twig processor
 */
class TwigSupraPageGlobal extends TwigSupraGlobal
{
//	/**
//	 * @var array
//	 */
//	protected $currentThemeParameterValues;
//
//	/**
//	 * @var ThemeInterface
//	 */
//	protected $theme;
//
//	/**
//	 *
//	 */
//	protected $themeProvider;

	/**
	 * @var ResponseContext
	 */
	protected $responseContext;

	/**
	 * @return ResponseContext
	 */
	public function getResponseContext()
	{
		return $this->responseContext;
	}

	/**
	 * @param ResponseContext $responseContext
	 */
	public function setResponseContext(ResponseContext $responseContext = null)
	{
		$this->responseContext = $responseContext;
	}

	/**
	 * Returns if in CMS mode.
	 * 
	 * @return bool
	 */
	public function isCmsRequest()
	{
		return $this->getRequest() instanceof PageRequestEdit;
	}

	/**
	 * Whether the passed link is actual - is some descendant opened currently.
	 * 
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

		if (! $localization instanceof PageLocalization) {
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
	 * @return null|Localization
	 */
	public function getLocalization()
	{
		if (! $this->request instanceof PageRequest) {
			return null;
		}

		$localization = $this->request->getPageLocalization();

		if (! $localization instanceof Localization) {
			return null;
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

		if ($localization === null) {
			return null;
		}

		$title = $localization->getTitle();
		$htmlTag = new HtmlTag($tagName, $title);

		if ($this->isCmsRequest()) {
			$htmlTag->addClass('su-settings-title');
		}

		return $htmlTag;
	}

}
