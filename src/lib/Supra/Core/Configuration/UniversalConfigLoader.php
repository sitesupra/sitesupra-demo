<?php

namespace Supra\Core\Configuration;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class UniversalConfigLoader implements ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function load($file)
	{
		if (!is_file($file) || !is_readable($file)) {
			throw new Exception\ConfigLoaderException(
					sprintf('File "%s" is not readable or does not exist', $file)
					);
		}

		return $this->container->getCache()
			->fetch('config', $file, function () use ($file) {
				$info = pathinfo($file);

				$data = file_get_contents($file);

				switch (strtolower($info['extension'])) {
					case 'yml':
						$data = Yaml::parse($data);
						break;
					default:
						throw new Exception\ConfigLoaderException(
							sprintf('File "%s" is not supported', $file)
						);
				}

				return $data ? $data : array();
			}, filemtime($file));
	}
}