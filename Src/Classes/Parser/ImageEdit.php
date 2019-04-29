<?php
namespace Parser;

use Exception;
use Imagick;
use ImagickException;


class ImageEdit {
    /**
     *
     * @throws Exception, ImagickException
     */
    public static function watermark($origImage, $destImage, $watermark = '/imagick.png') {

        try {
            $image = new Imagick();
            $image->readImage($origImage);

            $watermark = new Imagick();
            $watermark->readImage($watermark);

            $iWidth = $image->getImageWidth();
            $iHeight = $image->getImageHeight();
            $wWidth = $watermark->getImageWidth();
            $wHeight = $watermark->getImageHeight();

            if ($iHeight < $wHeight || $iWidth < $wWidth) {
                $watermark->scaleImage($iWidth/4, $iHeight/4);

                $wWidth = $watermark->getImageWidth();
                $wHeight = $watermark->getImageHeight();
            }

            $x = ($iWidth - $wWidth) / 2;
            $y = ($iHeight - $wHeight) / 2;

            $image->compositeImage($watermark, imagick::COMPOSITE_OVER, $x+270, $y+300);
            if ($image->writeImage($destImage))
                return true;

        } catch (ImagickException $e) {
            throw new Exception($e);
        }

        return false;

    }
}
