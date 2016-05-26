<?php

namespace Site\UploadBundle\Form;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\Type;

class CropType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('w', 'number', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"numeric"))
                )
            ))
            ->add('h', 'number', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"numeric"))
                )
            ))
            ->add('x', 'number', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"numeric"))
                )
            ))
            ->add('y', 'number', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"numeric"))
                )
            ))
            ->add('x2', 'number', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"numeric"))
                )
            ))
            ->add('y2', 'number', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"numeric"))
                )
            ))
            ->add('path', 'text', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"string"))
                )
            ))
            ->add('field', 'text', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"string"))
                )
            ))
            ->add('type', 'text', array(
                'constraints'=>array(
                    new NotBlank(),
                    new Type(array('type'=>"string"))
                )
            ))
        ;
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
        ));
    }
//    public function setDefaultOptions(OptionsResolverInterface $resolver) {
//        /** @var OptionResolver $resolver */
//        $this->configureOptions($resolver);
//    }
    public function getName()
    {
        return 'site_uploadbundle_croptype';
    }
}
