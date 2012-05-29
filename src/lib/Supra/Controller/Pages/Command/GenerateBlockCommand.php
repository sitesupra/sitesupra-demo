<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;

class GenerateBlockCommand extends Command
{

	private $classTemplate = '<?php
namespace {namespace};
		
use Supra\Controller\Pages\BlockController;
use Supra\Editable;

class {className} extends BlockController
{

	public static function getPropertyDefinition()
	{
		$properties = array();
		
		// code

		return $properties;
	}

	protected function doExecute()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		
		// code
		
		$response->outputTemplate(\'index.html.twig\');
	}
	
}
';
	private $configTemplate = '- Supra\Controller\Pages\Configuration\BlockControllerConfiguration:
    class: {className}
    title: {title}
    groupId: features
    description: Description
    cmsClassname: Editable
    cache:
      Supra\Controller\Pages\Configuration\BlockControllerCacheConfiguration:
        enabled: true
        global: false
        groups:
          - Supra\FileStorage
          - Supra\Controller\Pages
';

	protected function configure()
	{
		$this->setName('su:generate:block')
				->setDescription('Creates new block')
				->addArgument('name', Console\Input\InputArgument::REQUIRED, 'Block name');
	}

	protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
	{
		$blockName = ucfirst($input->getArgument('name'));

		$blockPath = SUPRA_COMPONENT_PATH . 'Blocks' . DIRECTORY_SEPARATOR;

		$blockDir = $blockPath . $blockName . DIRECTORY_SEPARATOR;

		mkdir($blockDir);

		// PHP class
		file_put_contents($blockDir . $blockName . 'Block.php', strtr($this->classTemplate, array(
					'{namespace}' => "Project\\Blocks\\$blockName",
					'{className}' => "{$blockName}Block",
				)));


		//twig file
		file_put_contents($blockDir . 'index.html.twig', '{# empty twig template #}');
		
		//config yaml
		file_put_contents($blockDir . 'config.yml', strtr($this->configTemplate, array(
					'{className}' => "Project\\Blocks\\$blockName\\{$blockName}Block",
					'{title}' => "$blockName Block",
				)));
	}

}