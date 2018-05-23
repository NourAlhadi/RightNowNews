<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('avatar',FileType::class, array(
                'label' => 'الصورة الشخصية: ',
                'required' => false,
                'data_class' => null
            ))
            ->add('fname', TextType::class, array(
                'label' => 'الاسم الأول: ',
                'required' => false

            ))
            ->add('lname', TextType::class, array(
                'label' => 'الاسم الثاني: ',
                'required' => false
            ))
            ->add('types',EntityType::class,array(
                'class' => 'AppBundle:type',
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'أخباري المفضلة: ',
                'required' => false
            ));
    }/**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_user';
    }


}
