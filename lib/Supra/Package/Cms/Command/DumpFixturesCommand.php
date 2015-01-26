<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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
