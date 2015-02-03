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

namespace Sample\Fixtures;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Sp\FixtureDumper\Generator\Alice\YamlFixtureGenerator;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\Entity\Abstraction\Localization;
use Supra\Package\Cms\Entity\ApplicationLocalization;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\BlockPropertyMetadata;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\PageLocalizationPath;
use Supra\Package\Cms\Entity\PagePlaceHolder;
use Supra\Package\Cms\Entity\TemplateLayout;
use Supra\Package\Cms\Entity\TemplateLocalization;
use Supra\Package\Cms\Entity\TemplatePlaceHolder;

class SupraYamlFixtureGenerator extends YamlFixtureGenerator
{
    protected function getDefaultVisitor()
    {
        return new YamlVisitor();
    }

    protected function readProperty($object, $property)
    {
        $reflectionClass = new \ReflectionClass(get_class($object));

        $reflectionProperty = $reflectionClass->getProperty($property);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    protected function getModels(ClassMetadata $metadata)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadataInfo */
        if ($metadata->isMappedSuperclass) {
            return array();
        }

        if (! empty($metadata->subClasses)) {
            $reflection = new \ReflectionClass($metadata->name);
            if ($reflection->isAbstract()) {
                return array();
            }
        }

        $models = $this->getManager()->getRepository($metadata->getName())->findAll();

        foreach ($models as $key => $model) {
            if (get_class($model) !== $metadata->name) {
                unset($models[$key]);
            }
        }

        return $models;
    }

    protected function processFieldNames(ClassMetadata $metadata, $model)
    {
        $data = parent::processFieldNames($metadata, $model);

        $data['id'] = $model->getId();

        return $data;
    }

    protected function prepareData(ClassMetadata $metadata, array $data)
    {
        foreach ($data as $name => &$entityData) {

            switch ($metadata->name) {
                case Localization::CN():
                case PageLocalization::CN():
                case TemplateLocalization::CN():
                case ApplicationLocalization::CN():
                    $entityData['publishedRevision'] = null;
                    $args = array($entityData['locale']);
                    break;

                case PageLocalizationPath::CN():
                    $args = array(str_ireplace('pageLocalizationPath', '', $name), $entityData['locale']);
                    break;

                case TemplateLayout::CN():
                    $args = array($entityData['media']);
                    break;

                case BlockPropertyMetadata::CN():
                    $args = array($entityData['name'], $entityData['blockProperty']);
                    break;

                case BlockProperty::CN():
                    $args = array($entityData['name']);
                    break;

                case PagePlaceHolder::CN():
                case TemplatePlaceHolder::CN():
                    $args = array($entityData['name']);
                    break;

                default:

                    $constructor = new \ReflectionMethod($metadata->name, '__construct');

                    if ($constructor->getNumberOfRequiredParameters() === 0) {
                        return parent::prepareData($metadata, $data);
                    }

                    throw new \RuntimeException(
                        sprintf(
                            'Don\'t know how to build constructor required for [%s].',
                            $metadata->name
                        )
                    );
            }

            $entityData = array_merge(array('__construct' => $args), $entityData);
        }

        return parent::prepareData($metadata, $data);
    }
}