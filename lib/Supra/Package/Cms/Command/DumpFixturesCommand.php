<?php

namespace Supra\Package\Cms\Command;

use Doctrine\ORM\EntityManager;
use Supra\Core\Console\AbstractCommand;
use Supra\Package\CmsAuthentication\Entity\Group;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DumpFixturesCommand extends AbstractCommand
{
    /**
     * @var EntityManager
     */
    protected $em;

    protected $entityMap = array();

    protected $entityAliasMap = array(
        'Supra\Package\CmsAuthentication\Entity\Group' => 'group'
    );

    protected function configure()
    {
        $this->setName('supra:fixtures:dump')
            ->setDescription('Dumps fixtures from database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->container->getDoctrine()->getManager();
        /* @var $em EntityManager */

        $data = array();

        //groups
        foreach ($this->em->getRepository('CmsAuthentication:Group')->findAll() as $group) {
            /* @var $group Group */
            $name = $this->registerEntity($group);

            $data[$name] = array('name' => $group->getName(), 'isSuper' => $group->isSuper());
        }

        $output->writeln(Yaml::dump(array('group' => $data), 2));
    }

    protected function registerEntity($entity)
    {
        $class = get_class($entity);

        if (!isset($this->entityAliasMap[$class])) {
            throw new \Exception(sprintF('Entity "%s" is not known', $class));
        }

        $alias = $this->entityAliasMap[$class];

        $name = null;

        switch ($alias) {
            case 'group':
                $name = $entity->getName();
                break;
            default:
                throw new \Exception(sprintf('Can not resolve entity name for class "%s"', $class));
                break;
        }

        $this->entityMap[$alias][$name] = $entity;

        return $name;
    }
}
