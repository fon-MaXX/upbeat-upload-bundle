<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 11/6/2015
 * Time: 11:53 AM
 */
namespace Site\UploadBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use \Twig_Extension;

class JsonDetectExtension extends Twig_Extension
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'json_detect.extension';
    }

    public function getFilters() {
        return array(
            'json_detect'   => new \Twig_Filter_Method($this, 'jsonDetect'),
        );
    }

    public function jsonDetect($str) {
        $result = json_decode($str);
        if (json_last_error() === JSON_ERROR_NONE) {
            return true;
        }
        return false;
    }
}