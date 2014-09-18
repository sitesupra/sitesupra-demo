<?php

namespace Supra\Package\Framework\Command;

use Supra\Core\Console\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
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

		$table = new Table($output);

		$table->setHeaders(array('Name', 'Pattern', 'Controller', 'Frontend'));

		foreach ($this->listRoutes($this->container->getRouter()->getRouteCollection()) as $route) {
			$table->addRow(array(
				$route['name'],
				$route['pattern'],
				$route['controller'],
				$route['frontend'] ? '<info>Yes</info>' : 'No'
			));
		}

		$table->render();
	}

	protected function listRoutes($routes)
	{
		$displayRoutes = array();

		foreach ($routes as $name => $route) {
			if ($route instanceof Route) {
				$displayRoutes[] = array(
					'name' => $name,
					'pattern' => $route->getPattern(),
					'controller' => $route->getDefault('controller'),
					'frontend' => $route->getOption('frontend')
				);
			} else {
				throw new \Exception('Only Route routes are supported now');
			}
		}

		return $displayRoutes;
	}

}
