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
					sprintf('File "%s" is not readable or not exists', $file)
					);
		}

		$info = pathinfo($file);

		$data = file_get_contents($file);

		$data = $this->processContainerParameters($data);

		switch (strtolower($info['extension'])) {
			case 'yml':
				$data = Yaml::parse($data);
				break;
			default:
				throw new Exception\ConfigLoaderException(
						sprintf('File "%s" is not supported', $file)
						);
		}

		return $data;
	}

	protected function processContainerParameters($data)
	{
		preg_match_all('/%[a-z\\._]+%/i', $data, $matches);

		$replacements = array();

		foreach ($matches as $expression) {
			$parameter = trim($expression[0], '%');

			$replacements[$expression[0]] = $this->container->getParameter($parameter);
		}

		$data = strtr($data, $replacements);

		return $data;
	}
}