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

class JsonDecodeExtension extends Twig_Extension
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getName()
    {
        return 'json_decode.extension';
    }

    public function getFilters() {
        return array(
            'json_decode'   => new \Twig_Filter_Method($this, 'jsonDecode'),
        );
    }

    public function jsonDecode($str) {
        return json_decode($str,true);
    }
}