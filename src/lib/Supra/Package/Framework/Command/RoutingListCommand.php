<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Route;

class RoutingListCommand extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('framework:routing:list');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<info>Defined routes:</info>');

		$routes = $this->container->getRouter()->getRouteCollection();

		$routes = $this->listRoutes($routes, $output);

		$output->writeln(str_repeat('-', 30 + 60 + 60 + 10));
		$output->writeln(sprintf('| %-30s | %-60s | %-60s |', 'Name', 'Pattern', 'Controller'));
		$output->writeln(str_repeat('-', 30 + 60 + 60 + 10));

		foreach ($routes as $route) {
			$output->writeln(sprintf('| %-30s | %-60s | %-60s |', $route['name'], $route['pattern'], $route['controller']));
		}

		$output->writeln(str_repeat('-', 30 + 60 + 60 + 10));
	}

	protected function listRoutes($routes, OutputInterface $output)
	{
		$displayRoutes = array();

		foreach ($routes as $name => $route) {
			if ($route instanceof Route) {
				$displayRoutes[] = array(
					'name' => $name,
					'pattern' => $route->getPattern(),
					'controller' => $route->getDefaults()['controller']
				);
			} else {
				throw new \Exception('Only Route routes are supported now');
			}
		}

		return $displayRoutes;
	}

}
