<?php

namespace Supra\Core\Package;
use Supra\Core\Supra;

/**
 * @todo: very global todo: remove this class and re-implement it info Supra cos it manages packages, not some static hardcode
 */
class PackageLocator
{
	/**
	 * @var Supra
	 */
	protected static $supra;

	protected static $configPath = 'Resources/config';
	protected static $viewPath = 'Resources/view';
	protected static $publicPath = 'Resources/public';

	public static function locateConfigFile($package, $name)
	{
		$path = self::locatePackageRoot($package)
				. DIRECTORY_SEPARATOR . self::$configPath
				. DIRECTORY_SEPARATOR . $name;

		if (!realpath($path) || !is_readable($path)) {
			throw new Exception\PackageLocatorException(
					sprintf('Config file "%s" for package "%s" (%s) can not be resolved (expected location "%s")',
							$name, self::formatName($package), self::formatClass($package), $path
							)
					);
		}

		return $path;
	}

	public static function setSupra(Supra $supra)
	{
		self::$supra = $supra;
	}

	public static function locatePackageRoot($package)
	{
		if (is_string($package) && !class_exists($package)) {
			$package = self::$supra->resolvePackage($package);
		}

		$reflection = new \ReflectionClass($package);

		return dirname($reflection->getFileName());
	}

	public static function locateViewFile($package, $name)
	{
		if (is_string($package) && !class_exists($package)) {
			$package = self::$supra->resolvePackage($package);
		}

		$path = self::locatePackageRoot($package)
			. DIRECTORY_SEPARATOR . self::$viewPath
			. DIRECTORY_SEPARATOR . $name;

		if (!realpath($path) || !is_readable($path)) {
			throw new Exception\PackageLocatorException(
				sprintf('View file "%s" for package "%s" (%s) can not be resolved (expected location "%s")',
					$name, self::formatName($package), self::formatClass($package), $path
				)
			);
		}

		return $path;
	}

	public static function formatClass($package)
	{
		if (is_object($package)) {
			$package = get_class($package);
		}

		if (!class_exists($package)) {
			throw new Exception\PackageLocatorException(
					sprintf('Can not resolve package class name for reference "%s"',
							$package
							)
					);
		}

		return $package;
	}

	public static function formatName($package)
	{
		$class = self::formatClass($package);

		$class = explode('\\', $class);

		return $class[count($class) - 1];
	}

	public static function locatePublicFolder($package)
	{
		if (is_string($package) && !class_exists($package)) {
			$package = self::$supra->resolvePackage($package);
		}

		return self::locatePackageRoot($package)
			. DIRECTORY_SEPARATOR . self::$publicPath;
	}

}
