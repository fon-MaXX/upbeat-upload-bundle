<?php

namespace Site\UploadBundle\Services;

use Imagick;
use RecursiveIteratorIterator;

/**
 * Class FileHandler
 * @package Site\UploadBundle\Services
 * methods:
 * cropImage - simple crop for jcrop
 * handleFileAndSave - make thumbnails from config,save files
 * handleCoverImageFile - composite`s image with cover pattern
 * makePerspective - make perspective view of image
 * makeCoverLeftPerspectiveThumbnail - make perspective image with front side
 * clearUploadDir - clear upload directory
 */
class FileHandler
{
    protected $session;
    protected $config;
    protected $sessionAttr;
    protected $uploadTempDir;
    protected $webDir;
    protected $rootDir;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param $config
     * @param $uploadDir
     * @param $webDir
     * @param $rootDir
     */
    public function __construct(\Symfony\Component\HttpFoundation\Session\SessionInterface $session, $config,$uploadDir,$webDir,$rootDir)
    {
        $this->session = $session;
        $this->config = $config;
        $this->webDir = $webDir;
        $this->uploadTempDir=$uploadDir;
        $this->rootDir=realpath($rootDir);
    }

    /**
     * crop image and save to temp directory
     * @param $path
     * @param $arr
     * return path to file
     * @return string
     */
    public function cropImage($path,$arr)
    {
        $file=realpath($this->rootDir).'/../'.$this->webDir.'/'.$path;
        $image=new \Imagick($file);
        $image->cropImage((integer)$arr["width"],(integer)$arr['height'],(integer)$arr['x'],(integer)$arr['y']);
        $image->setImageFormat('png');
        $name='/'.$this->uploadTempDir.'/crop_'.uniqid().'.png';
        $image->writeImage(realpath($this->rootDir.'/../'.$this->webDir).'/'.$name);
        return $name;
    }

    /**
     * handle files in order to config
     * @param $field - field name
     * @param $uploadDir - directory like /uploads/products/1/
     * @param bool $json
     * @return array|bool|string
     * @throws \Exception
     */
    public function handleFileAndSave($file,$subDir, $json = true)
    {
        if($json){
            $file=json_decode($file,true);
            $file=json_decode(reset($file),true);
        }
        $filePath=$file['path'];
        $fieldType=$file['file_type'];
        $uploadDir=$this->config[$fieldType]['upload_dir'];
        $dir=$uploadDir.$subDir;
        if ($this->config[$fieldType]['type'] == 'file') {
                $result = $this->saveFile($filePath, $dir);
            }
        elseif ($this->config[$fieldType]['type'] == 'image') {
                $result = $this->saveImage($filePath, $dir, $this->config[$fieldType]['thumbnails']);
            }
            else {
                throw new \Exception('Unrecognized file type!');
            }
            return $result;
    }

    /**
     * makes non-transparent cover images for CoverConstructorController
     * @param $pattern json-array
     * @param $mask - path to mask img
     * @param $shadows - path to shadow img
     * @return string path to ready image
     */
    public function makeNonTransparent($pattern,$mask,$shadows)
    {
        $maskImg= $this->prepareImgFile($mask,true);
        $shadowImg = $this->prepareImgFile($shadows,true);
        $patternImg=$this->prepareImgFile($pattern,true);
        $patternImg=$this->preparePatternImage($patternImg,$maskImg->getImageWidth(),$maskImg->getImageHeight());
        $patternImg->compositeImage($maskImg, Imagick::COMPOSITE_DSTIN, 0, 0, Imagick::CHANNEL_ALPHA);
        $patternImg->compositeImage($shadowImg, Imagick::COMPOSITE_ATOP, 0, 0);
        $name='/'.$this->uploadTempDir.'/non_transparent_cover_'.uniqid().'.png';
        $patternImg->writeImage($this->rootDir.'/../'.$this->webDir.$name);
        return $name;
    }

    /**
     * Generates image fo semi-transparent cover
     *
     * @param $alphaPath - json-string path to alpha-chanel patern file
     * @param $semiTransparentPath - json-string path to semi-transparent pattern file
     * @param $matrix - path to phone colored image(white-phone,black and so on)
     * @param $mask - path to phone mask file
     * @param $shadows - path to phone shadows file
     * @return string - path to generated file
     */
    public function makeTransparent($alphaPath,$semiTransparentPath,$matrix,$mask,$shadows)
    {
        $maskImg= $this->prepareImgFile($mask,true);
        $shadowImg = $this->prepareImgFile($shadows,true);
        $matrixImg = $this->prepareImgFile($matrix,true);

        $alphaImg=$this->prepareImgFile($alphaPath,true);
        $alphaImg=$this->preparePatternImage($alphaImg,$maskImg->getImageWidth(),$maskImg->getImageHeight());

        $semiTransparentImg=$this->prepareImgFile($semiTransparentPath,true);
        $semiTransparentImg=$this->preparePatternImage($semiTransparentImg,$maskImg->getImageWidth(),$maskImg->getImageHeight());

        $matrixImg->compositeImage($semiTransparentImg, Imagick::COMPOSITE_HARDLIGHT, 0, 0);
        $matrixImg->compositeImage($alphaImg, Imagick::COMPOSITE_ATOP, 0, 0);
        $matrixImg->compositeImage($maskImg, Imagick::COMPOSITE_DSTIN, 0, 0, Imagick::CHANNEL_ALPHA);
        $matrixImg->compositeImage($shadowImg, Imagick::COMPOSITE_ATOP, 0, 0);


        $name='/'.$this->uploadTempDir.'/transparent_cover_'.uniqid().'.png';
        $matrixImg->writeImage($this->rootDir.'/../'.$this->webDir.$name);
        return $name;
    }

    /**
     * @param $path
     * @param bool $json_detect  - whether path json-string
     * @return Imagick
     */
    private function prepareImgFile($path,$json_detect=false)
    {
        if($json_detect){
            $arrayPath = json_decode($path,true);
            $path=$arrayPath['default_file'];
        }
        $file = realpath($this->rootDir).'/../'.$this->webDir.$path;
        $img = new \Imagick($file);
        $img->setImageMatte(true);
        return $img;
    }
    /**
     * resize`s pattern image for mask height, then crop by center with mask width
     * @param $img - Imagick object
     * @param $width - mask width
     * @param $height - mask height
     * @return object - prepared Imagick object
     */
    private function preparePatternImage(\Imagick $img,$width,$height)
    {
        $img->scaleImage(0, $height);
        $imgWidth=$img->getImageWidth();
        $cropPad=($imgWidth-$width)*0.5;
        $img->cropImage($width,$height,$cropPad,0);
        return $img;

    }
    /**
     * composite image with cover-pattern image
     * @param $configType - name im config
     * @param $width
     * @param $height
     * @param $coverPattern - path to pattern /path/img.png
     * @return string|bool - path to composite image
     * @throws \ErrorException
     * @throws \Exception
     */
    public function handleCoverImageFile($path,$configType,$width,$height,$coverPattern)
    {
        $config=$this->config[$configType];
        if ($config['type'] !== 'custom-image') {
            throw new \Exception('Unrecognized file type!');
        }
        $im=$this->performResize($path,$config['main_action'],$width,$height);
        $im->setImageFormat('png');
        $pattern = new Imagick($this->rootDir.'/../'.$this->webDir.'/'.$coverPattern);
        $im->compositeImage($pattern, imagick::COMPOSITE_OVER, 0, 0);
        $name=$this->uploadTempDir.'/pattern_'.uniqid().'.png';
        $im->writeImage($this->rootDir.'/../'.$this->webDir.'/'.$name);
        return $name;
    }
    /**
     *real path to image
     * like _root_web_dir_/uploads/images/somefile.jpg
     * @param string $sourcePath
     * 'left','right'
     * @param string $direction
     * like /uploads/images/left1235.png
     * @return string $resultPath
     */
    public function makePerspective($sourcePath,$direction)
    {
        $dir=$this->rootDir.'/../'.$this->webDir.$sourcePath;
        $im = new \Imagick($dir);
        //resize image width for better perspective-look
        $newWidth=$im->getImageWidth()*0.3;
        $im->resizeImage($newWidth,$im->getImageHeight(),Imagick::FILTER_LANCZOS,1);
        //png- because of transparency
        $im->setImageFormat('png');
        /* Fill new visible areas with transparent */
        $im->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        /* Activate matte */
        $im->setImageMatte(true);
        $im=$this->makePerspectiveDistortion($direction,$im);
        /* Ouput the image */
        $resultPath=$this->uploadTempDir.'/'.$direction.'_'.$im->getImageFilename().'.png';
        $im->writeImage($this->rootDir.'/../'.$this->webDir.'/'.$resultPath);
        return $resultPath;

    }
    /**
     * clear upload directory,defined in config
     */
    public function clearUploadDir()
    {
        $dir = '/'.$this->uploadTempDir;
        $this->clearDirectory($dir);
//        if (!is_dir($dir)){
//            return;
//        }
//        $it = new \RecursiveDirectoryIterator($dir);
//        $files = new \RecursiveIteratorIterator($it,
//            RecursiveIteratorIterator::CHILD_FIRST);
//        foreach($files as $file) {
//            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
//                continue;
//            }
//            if ($file->isDir()){
//                rmdir($file->getRealPath());
//            } else {
//                unlink($file->getRealPath());
//            }
//        }
//        rmdir($dir);
    }
    public function clearDirectory($dir,$full=false)
    {
        if(($dir=="/bundles/sitebackend/images/test-images/")||($dir=="/bundles/sitebackend/images/test-images"))
        {
            return;
        }
        $dir = $this->rootDir.'/../'.$this->webDir.$dir;
        if (!is_dir($dir)){
            return;
        }
        $it = new \RecursiveDirectoryIterator($dir);
        $files = new \RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        if($full){
            rmdir($dir);
        }
    }
    public function makeCoverLeftPerspectiveThumbnail($path)
    {
        $dir=$this->rootDir.'/../'.$this->webDir.$path;
        $im = new \Imagick($dir);
        $newWidth=$im->getImageWidth()*0.25;
        $im->resizeImage($newWidth,$im->getImageHeight(),Imagick::FILTER_LANCZOS,1);
        $im->setImageFormat('png');
        $copy= clone $im;
        $im->cropImage($im->getImageWidth()*6/7,$im->getImageHeight(),0,0);
        $copy->cropImage($copy->getImageWidth()*1/7,$copy->getImageHeight(),$copy->getImageWidth()*6/7,0);
        /* Fill new visible areas with transparent */
        $im->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        $copy->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
        /* Activate matte */
        $im->setImageMatte(true);
        $copy->setImageMatte(true);
        $im=$this->makePerspectiveDistortion('left',$im);
        $copy=$this->makePerspectiveDistortion('right',$copy);
        $new=new \Imagick();
        /* Create new checkerboard pattern */
        $new->newPseudoImage($im->getImageWidth()+$copy->getImageWidth(), $im->getImageHeight(), "pattern:checkerboard");

        /* Set the image format to png */
        $new->setImageFormat('png');
        $new->compositeImage($im, imagick::COMPOSITE_OVER, 0, 0);
        $new->compositeImage($copy, imagick::COMPOSITE_OVER, $im->getImageWidth(), 0);
        $resultPath=$this->uploadTempDir.'/left_right'.'_'.uniqid().'.png';
        //TODO may be have to compose with cover background
        $im->writeImage($this->rootDir.'/../'.$this->webDir.'/'.$resultPath);
        return $resultPath;
    }

    /**
     * make perspective distortion,return changed imagick object
     * @param $direction
     * @param $im
     *
     * @return mixed
     */
    private function makePerspectiveDistortion($direction,$im)
    {
        /* for angle 15 degrees*/
        $dt=$im->getImageWidth()*0.268;
        /* Control points for the distortion */
        switch ($direction)
        {
            case 'left':
                $controlPoints = array(
                    0, -$dt, /* push left bot angle x, push right top angle y and enlarge height */
                    0, 0,/* push left top angle x, push left top angle y*/

                    0, $im->getImageHeight()  + 0,/* push left top angle x and enlarge width, push left bot angle y */
                    0, $im->getImageHeight() - $dt,/* push left bot angle x, push right bot angle y and enlarge height */

                    $im->getImageWidth() + 0, 0,/* push right top angle x, push left top angle y and enlarge height*/
                    $im->getImageWidth() + 0, 0,/* push right bottom angle and enlarge width, push right top angle y */

                    $im->getImageWidth() + 0, $im->getImageHeight() + 0,/* push right bot angle x, push right bot angle y */
                    $im->getImageWidth() + 0, $im->getImageHeight() + 0 /* push right top angle x and enlarge width,  push left bot angle y and enlarge height */
                );
                break;
            case 'right':
            {
                $controlPoints = array(
                    0, 0, /* push left bot angle x, push right top angle y and enlarge height */
                    0, 0,/* push left top angle x, push left top angle y*/

                    0, $im->getImageHeight()  - 0,/* push left top angle x and enlarge width, push left bot angle y */
                    0, $im->getImageHeight() - 0,/* push left bot angle x, push right bot angle y and enlarge height */

                    $im->getImageWidth() + 0, -$dt,/* push right top angle x, push left top angle y and enlarge height*/
                    $im->getImageWidth() + 0, 0,/* push right bottom angle and enlarge width, push right top angle y */

                    $im->getImageWidth() + 0, $im->getImageHeight()  - 0,/* push right bot angle x, push right bot angle y */
                    $im->getImageWidth() + 0, $im->getImageHeight() - $dt /* push right top angle x and enlarge width,  push left bot angle y and enlarge height */
                );
            }
        }
        /* Perform the distortion */
        $im->distortImage(Imagick::DISTORTION_PERSPECTIVE, $controlPoints, false);
        return $im;
    }

    /**
     * @param $dir
     */
    private function checkDir($dir)
    {
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        chmod($dir, 0777);

    }
    /**
     * $arr['im_width']
     * $arr['im_height']
     * $arr['th_width']
     * $arr['th_height']
     * @param array $arr
     * resize parameters: width and height
     * @return array $sizes
     */
    private function getCropResizeParameters($arr)
    {
        $coeff=$arr['im_height']/$arr['im_width'];
        $resize=array();
        if(($arr['th_width']<$arr['im_width'])&&($arr['th_height']>$arr['im_height'])){
            $resize['height']=$arr['th_height'];
            if($coeff>1){
                $resize['width']=$coeff*$arr['th_height'];
            }
            else{
                $resize['width']=$arr['th_height']/$coeff;
            }
        }
        elseif(($arr['th_width']>$arr['im_width'])&&($arr['th_height']<$arr['im_height'])){
            $resize['width']=$arr['th_width'];
            if($coeff>1){
                $resize['height']=$arr['th_width']*$coeff;
            }
            else{
                $resize['height']=$arr['th_width']/$coeff;
            }
        }
        elseif(($arr['th_width']<$arr['im_width'])&&($arr['th_height']>$arr['im_height'])){
            $size=max($arr['th_width'],$arr['th_height']);
            if($coeff>1){
                $resize['height']=$size*$coeff;
                $resize['width']=$size;
            }
            else{
                $resize['height']=$size;
                $resize['width']=$size*$coeff;
            }
        }
        return $resize;
    }

    /**
     * simple save files
     * @param $path - path to file
     * @param $dir - where to save file
     * @return array
     */
    private function saveFile($path, $dir)
    {
        $fullDirPath=$this->rootDir.'/../'.$this->webDir.$dir;
        $fullPath=$this->rootDir.'/../'.$this->webDir.$path;
        $this->checkDir($fullDirPath);
        $result = array();
        $fileAttr=pathinfo($fullPath);
        $fileName = uniqid();
        rename ($fullPath, $fullDirPath.'/'.$fileName.'.'.$fileAttr['extension']);
        $result[] = $dir.'/'.$fileName.'.'.$fileAttr['extension'];
        return $result;
    }

    /**
     * @param $filePath - file in temp dir
     * @param $destinationDir
     * @param $thumbs
     * @return array
     * @throws \ErrorException
     */
    private function saveImage($filePath, $destinationDir, $thumbs)
    {
        $fullDestDir=$this->rootDir.'/../'.$this->webDir.$destinationDir;
        $this->checkDir($fullDestDir);
        $result = array();
        $fullPath=$this->rootDir.'/../'.$this->webDir.$filePath;
        if(!file_exists($fullPath)){
            throw new \Exception('FileHandler::SaveImage - no file present');
        }
        $fileAttr=pathinfo($fullPath);
        copy($fullPath, $fullDestDir.'/'.$fileAttr['basename']);
        $result['default_file'] = $destinationDir.'/'.$fileAttr['basename'];
        foreach ($thumbs as $key => $thumb)
        {
        if (isset($thumb['action']) == true) {
            $img=$this->performResize($fullPath,$thumb['action'],$thumb['width'],$thumb['height']);
            }
        if (isset($thumb['watermark']) == true) {
            $watermark= new \Imagick(__DIR__.'/../Resources/public/images/watermarks/'.$thumb['watermark']);
            $watermark->setImageFormat('png');
            $watermark->setImageOpacity($thumb['opacity']);
            $paddingX=$img->getImageWidth()-$watermark->getImageWidth()-$thumb['padding-x'];
            $paddingY=$img->getImageHeight()-$watermark->getImageHeight()-$thumb['padding-y'];
            $img->compositeImage($watermark, imagick::COMPOSITE_OVER, $paddingX, $paddingY);
            }
        $this->checkDir($fullDestDir);
        $name = $key.uniqid().'.png';
        $path=$fullDestDir.'/'.$name;
        $img->writeImage($path);
        $result[$key] = $destinationDir.'/'.$name;
        }
//        unlink($fullPath);
        return $result;
    }

    /**
     * @param $path
     * @param $action
     * @param $width
     * @param $height
     * @return Imagick
     * @throws \ErrorException
     */
    private function performResize($path,$action,$width,$height)
    {
        $img= new \Imagick($path);
        $img->setImageFormat('png');
        if(!$img->getImageHeight()){
            throw new \ErrorException('Error while file handling');
        }
        switch ($action) {
            case "exact_resize":
                $img->resizeImage($width, $height,Imagick::FILTER_LANCZOS,1,true);
                break;
            case "landscape_resize":
                $img->resizeImage($width, null,Imagick::FILTER_LANCZOS,1,true);
                break;
            case "portrait_resize":
                $img->resizeImage(null, $height,Imagick::FILTER_LANCZOS,1,true);
                break;
            case "exact_crop":
                if($img->getImageWidth()<$width||$img->getImageHeight()<$height){
                    $arr=array();
                    $arr['th_height']=$height;
                    $arr['th_width']=$width;
                    $arr['im_width']=$img->getImageWidth();
                    $arr['im_height']=$img->getImageHeight();
                    $resize=$this->getCropResizeParameters($arr);
                    $img->resizeImage($resize['width'], $resize['height'],Imagick::FILTER_LANCZOS,1);
                }
                $img->cropImage($width, $height,0,0);
                break;
            default:
                $img->resizeImage($width, $height,Imagick::FILTER_LANCZOS,1);
                break;
        }
        return $img;
    }
}