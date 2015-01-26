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

use Supra\Core\Console\AbstractCommand;
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\Template;
use Supra\Package\Cms\Entity\TemplateLayout;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\CmsAuthentication\Entity\Group;
use Supra\Package\CmsAuthentication\Entity\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\EntityManager;

class LoadFixturesCommand extends AbstractCommand
{
    protected $entityMap = array();

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    private $dataDir;

    protected function configure()
    {
        $this->setName('supra:fixtures:load')
            ->addArgument('filename', InputArgument::REQUIRED, 'YML file to load fixtures from (relative to root)')
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clears database before loading fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->container->getDoctrine()->getManager();
        /* @var $em EntityManager */

        $this->em->beginTransaction();

        $entities = array(
            'CmsAuthentication:User',
            'CmsAuthentication:Group',

            'Cms:BlockPropertyMetadata',
            'Cms:ReferencedElement\ReferencedElementAbstract',
            'Cms:BlockProperty',
            'Cms:Abstraction\Block',
            'Cms:Abstraction\PlaceHolder',
            'Cms:Abstraction\Localization',

            'Cms:PageLocalizationPath',

            'Cms:Page',

            'Cms:TemplateLayout',
            'Cms:Template',

            'Cms:ImageSize',
            'Cms:Image',
            'Cms:File',
            'Cms:Folder',
            'Cms:FilePath',
        );

        if ($input->getOption('clear')) {
            foreach ($entities as $entity) {
                //todo: also clean audit tables here
                $this->em->createQueryBuilder()
                    ->delete($entity)
                    ->getQuery()
                    ->execute();
            }
        }

        $dataFile = $this->container->getParameter('directories.project_root')
            . DIRECTORY_SEPARATOR . $input->getArgument('filename');

        if (! is_file($dataFile)) {
            throw new \RuntimeException(sprintf(
                'The file [%s] does not exists.', $dataFile
            ));
        }

        $this->dataDir = dirname($dataFile);

        //todo: validate it
        $data = Yaml::parse(file_get_contents($dataFile));

        //we need to maintain creation order
        foreach (array('group', 'user', 'image', 'template') as $section) {
            foreach ($data[$section] as $name => $definition) {
                $this->createEntity($section, $name, $definition);
            }
        }

        $this->em->flush();

        $this->em->commit();
    }

    protected function createEntity($section, $name, $data)
    {
        if (!isset($this->entityMap[$section])) {
            $this->entityMap[$section] = array();
        }

        if (isset($this->entityMap[$section][$name])) {
            return $this->entityMap[$section][$name];
        }

        $entity = call_user_func(array($this, 'createEntity'.ucfirst($section)), $data);

        return $this->entityMap[$section][$name] = $entity;
    }

    protected function resolveEntity($section, $name)
    {
        if (isset($this->entityMap[$section]) && isset($this->entityMap[$section][$name])) {
            return $this->entityMap[$section][$name];
        }

        //todo: try to create entity  here
        throw new \Exception(sprintf('Entity "%s" from section "%s" was not found', $name, $section));
    }

    protected function createEntityTemplate($data)
    {
        $template = new Template();
        $this->em->persist($template);

        $layout = new TemplateLayout($data['media']);
        $layout->setLayoutName($data['layoutName']);
        $layout->setTemplate($template);
        $this->em->persist($layout);

        foreach ($data['localizations'] as $locale => $title) {
            $localization = new TemplateLocalization($locale);
            $localization->setTitle($title);
            $localization->setTemplate($template);
            $this->em->persist($localization);
        }

        return $template;
    }

    protected function createEntityUser($data)
    {
        $user = new User();

        $encoder = $this->container['cms_authentication.encoder_factory']->getEncoder($user);

        $user->setName($data['name']);
        $user->setLogin($data['login']);
        $user->setPassword($encoder->encodePassword($data['password'], $user->getSalt()));
        $user->setEmail($data['email']);
        $user->setActive($data['active']);
        $user->setGroup($this->resolveEntity('group', $data['group']));
        $user->setRoles($data['roles']);

        $this->em->persist($user);
    }

    protected function createEntityGroup($data)
    {
        $group = new Group();
        $group->setName($data['name']);
        $group->setIsSuper($data['isSuper']);
        $this->em->persist($group);

        return $group;
    }

    protected function createEntityImage($data)
    {
        if (strpos($data['fileName'], '..')) {
            throw new \RuntimeException('Invalid file name.');
        }

        $fileName = $this->dataDir . DIRECTORY_SEPARATOR . $data['fileName'];

        $imageFile = new UploadedFile($fileName, $data['name']);

        $fileStorage = $this->container['cms.file_storage'];
        /* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

        if (! $fileStorage->isSupportedImageFormat($fileName)) {
            throw new \RuntimeException(sprintf(
                'The file [%s] format is not supported image file format [%s].', $fileName, $imageFile->getMimeType())
            );
        }

        $entity = new Image();
        $this->em->persist($entity);

        $entity->setFileName($data['name']);
        $entity->setSize($imageFile->getSize());
        $entity->setMimeType($imageFile->getMimeType());

        // store original size
        $imageProcessor = $fileStorage->getImageResizer();
        $imageInfo = $imageProcessor->getImageInfo($fileName);
        $entity->setWidth($imageInfo->getWidth());
        $entity->setHeight($imageInfo->getHeight());

        $fileStorage->validateFileUpload($entity, $fileName);

        $this->em->flush($entity);

        $fileStorage->storeFileData($entity, $fileName);

        return $entity;
    }
}
