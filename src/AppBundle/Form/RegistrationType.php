<?php
/**
 * Created by PhpStorm.
 * User: nouralhadi
 * Date: 5/25/18
 * Time: 11:17 PM
 */

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('fname')->add('lname')->add('types',EntityType::class,array(
            'class' => 'AppBundle:type',
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
            'label' => 'أخباري المفضلة: ',
            'required' => false
        ));
    }
    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }
}