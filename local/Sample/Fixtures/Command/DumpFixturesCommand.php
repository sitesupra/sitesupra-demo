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

use PhpCollection\Map;
use Sample\Fixtures\DateHandler;
use Sample\Fixtures\SupraYamlFixtureGenerator;
use Sp\FixtureDumper\Converter\Handler\HandlerRegistry;
use Sp\FixtureDumper\ORMDumper;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\ImageSize;
use Supra\Package\Cms\FileStorage\FileStorage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpFixturesCommand extends AbstractFixturesCommand
{
    protected function configure()
    {
        $this->setName('sample:fixtures:dump')
            ->addArgument('folder', InputArgument::REQUIRED)
            ->setDescription('Dumps demo fixtures.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folder = $input->getArgument('folder');

        if (strpos($folder, '.') !== false) {
            throw new \RuntimeException('Dots are not allowed.');
        }

        $folder = $this->container->getParameter('directories.project_root') . DIRECTORY_SEPARATOR . $folder;

        if (! is_dir($folder)) {
            throw new \RuntimeException(sprintf('Invalid directory [%s].'), $folder);
        }

        $registry = new HandlerRegistry();
        $registry->addSubscribingHandler(new DateHandler());

        $dumper = new ORMDumper(
            $this->container->getDoctrine()->getManager(),
            $registry,
            new Map(array('yml' => new SupraYamlFixtureGenerator()))
        );

        $dumper->setDumpMultipleFiles(false);

        $dumper->dump($folder, 'yml');

        //dump files
        $entityManager = $this->container->getDoctrine()->getManager();
        $fileStorage = $this->container['cms.file_storage'];
        /* @var $fileStorage FileStorage */

        foreach ($entityManager->getRepository(File::CN())->findAll() as $file) {
            /* @var $file File */

            if ($fileStorage->fileExists($file)) {
                copy($fileStorage->getFilesystemPath($file), $this->getFileTemporaryName($file, $folder));
            }

            if ($file instanceof Image) {

                foreach ($file->getImageSizeCollection() as $imageSize) {
                    /* @var $imageSize ImageSize */

                    $imageSizePath = $fileStorage->getImagePath($file, $imageSize->getName());

                    if (is_file($imageSizePath)) {
                        copy($imageSizePath, $this->getImageSizeTemporaryName($imageSize, $folder));
                    }
                }
            }
        }
    }
}
