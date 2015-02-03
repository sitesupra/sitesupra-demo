<?php

namespace Sample\Fixtures\Command;

use Supra\Core\Console\AbstractCommand;
use Supra\Package\Cms\Entity\File;
use Supra\Package\Cms\Entity\ImageSize;

abstract class AbstractFixturesCommand extends AbstractCommand
{
    /**
     * @param File $file
     * @param string $baseDirectory
     * @return string
     */
    protected function getFileTemporaryName(File $file, $baseDirectory)
    {
        return $baseDirectory . DIRECTORY_SEPARATOR . $file->getId() . '.file';
    }

    /**
     * @param ImageSize $imageSize
     * @param string $baseDirectory
     * @return string
     */
    protected function getImageSizeTemporaryName(ImageSize $imageSize, $baseDirectory)
    {
        return $baseDirectory . DIRECTORY_SEPARATOR . $imageSize->getId() . '.size';
    }
}