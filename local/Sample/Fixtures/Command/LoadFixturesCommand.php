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

namespace Sample\Fixtures\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Nelmio\Alice\Fixtures;
use SimpleThings\EntityAudit\AuditManager;
use Supra\Core\NestedSet\Listener\NestedSetListener;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\ImageSize;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\FileStorage\Listener\FilePathChangeListener;
use Supra\Package\Cms\Pages\Listener\ImageSizeCreatorListener;
use Supra\Package\Cms\Pages\Listener\PagePathGeneratorListener;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends AbstractFixturesCommand
{
    protected function configure()
    {
        $this->setName('sample:fixtures:load')
            ->addArgument('folder', InputArgument::REQUIRED, 'Dump data folder to load fixtures from (relative to root)')
            ->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clears database before loading fixtures');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dataFolder = $this->container->getParameter('directories.project_root')
            . DIRECTORY_SEPARATOR . $input->getArgument('folder');

        if (! is_dir($dataFolder)) {
            throw new \RuntimeException(sprintf(
                'The directory [%s] does not exists.', $dataFolder
            ));
        }

        $dataFile = $dataFolder . DIRECTORY_SEPARATOR . 'fixtures.yml';

        if (! is_file($dataFile)) {
            throw new \RuntimeException(sprintf(
                'The fixtures file [%s] does not exists.', $dataFile
            ));
        }

        $entityManager = $this->container->getDoctrine()->getManager();
        /* @var $entityManager EntityManager */

        $entityManager->beginTransaction();

        $entities = array(
            'CmsAuthentication:User',
            'CmsAuthentication:Group',
            'CmsAuthentication:AbstractUser',
            'CmsAuthentication:UserPreference',
            'CmsAuthentication:UserPreferencesCollection',

            'Cms:ApplicationLocalizationParameter',
            'Cms:BlockPropertyMetadata',
            'Cms:ReferencedElement\ReferencedElementAbstract',
            'Cms:ReferencedElement\ImageReferencedElement',
            'Cms:ReferencedElement\LinkReferencedElement',
            'Cms:ReferencedElement\MediaReferencedElement',
            'Cms:BlockProperty',
            'Cms:Abstraction\Block',
            'Cms:Abstraction\PlaceHolder',
            'Cms:LocalizationTag',
            'Cms:Abstraction\Localization',
            'Cms:Abstraction\RedirectTarget',
            'Cms:PageLocalizationPath',
            'Cms:Page',
            'Cms:GroupPage',
            'Cms:EditLock',
            'Cms:TemplateLayout',
            'Cms:Template',
            'Cms:Abstraction\AbstractPage',
            'Cms:FileProperty',
            'Cms:ImageSize',
            'Cms:Image',
            'Cms:File',
            'Cms:Folder',
            'Cms:FilePath',
            'Cms:Abstraction\File',
        );

        if ($input->getOption('clear')) {

            $auditManager = $this->container['entity_audit.manager'];
            /* @var $auditManager AuditManager */

            $metadata = array();

            foreach ($entities as $name) {

                $classMetadata = $entityManager->getClassMetadata($name);
                $metadata[] = $classMetadata;

                if ($auditManager->getMetadataFactory()->isAudited($classMetadata->name)) {

                    $tableName = $auditManager->getConfiguration()->getTablePrefix()
                        . $classMetadata->getTableName()
                        . $auditManager->getConfiguration()->getTableSuffix();

                    $entityManager->getConnection()->executeQuery(sprintf('DELETE FROM %s ', $tableName))->execute();
                }
            }

            $entityManager->getConnection()->executeQuery(
                sprintf('DELETE FROM %s ', $auditManager->getConfiguration()->getRevisionTableName())
            )->execute();

            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);

        }

        $evtManager = $entityManager->getEventManager();

        foreach ($evtManager->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof PagePathGeneratorListener
                    || $listener instanceof FilePathChangeListener
                    || $listener instanceof NestedSetListener
                    || $listener instanceof ImageSizeCreatorListener) {

                    $evtManager->removeEventListener($event, $listener);
                }
            }
        }

        Fixtures::load($dataFile, $entityManager, array('persist_once' => true));

        $entityManager->flush();

        // 'publish' pages
        $entityManager->createQuery(sprintf('UPDATE %s l SET l.publishedRevision = 1', Localization::CN()))->execute();

        $fileStorage = $this->getFileStorage();

        foreach ($entityManager->getRepository(File::CN())->findAll() as $file) {
            /* @var $file File */

            $tmpName = $this->getFileTemporaryName($file, $dataFolder);

            if (is_file($tmpName)) {
                $this->ensureFileDirectoryExists($file);
                copy(
                    $tmpName,
                    $fileStorage->getFilesystemPath($file)
                );
            }

            if ($file instanceof Image) {

                foreach ($file->getImageSizeCollection() as $imageSize) {
                    /* @var $imageSize ImageSize */

                    $tmpName = $this->getImageSizeTemporaryName($imageSize, $dataFolder);

                    if (is_file($tmpName)) {
                        $this->ensureImageSizeDirectoryExists($file, $imageSize->getName());

                        copy(
                            $tmpName,
                            $fileStorage->getImagePath($file, $imageSize->getName())
                        );
                    }
                }
            }
        }

        $entityManager->commit();
    }

    /**
     * @param File $file
     */
    private function ensureFileDirectoryExists(File $file)
    {
        $this->ensureDirectoryExists(
            dirname($this->getFileStorage()->getFilesystemPath($file))
        );
    }

    /**
     * @param Image $image
     * @param string $sizeName
     */
    private function ensureImageSizeDirectoryExists(Image $image, $sizeName)
    {
        $this->ensureDirectoryExists(
            dirname($this->getFileStorage()->getImagePath($image, $sizeName))
        );
    }

    /**
     * @param string $dirName
     * @throws \RuntimeException
     */
    private function ensureDirectoryExists($dirName)
    {
        if (! is_dir($dirName)) {
            if (true !== mkdir($dirName, $this->getFileStorage()->getFolderAccessMode(), true)) {
                throw new \RuntimeException(sprintf(
                    'Failed to create directory [%s]', $dirName
                ));
            }
        }
    }

    /**
     * @return FileStorage
     */
    private function getFileStorage()
    {
        return $this->container['cms.file_storage'];
    }
}
