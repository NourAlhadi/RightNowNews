<?php
/**
 * Created by PhpStorm.
 * User: nouralhadi
 * Date: 5/8/18
 * Time: 1:35 AM
 */

namespace AppBundle\Form;

use AppBundle\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sender')
            ->add('email',EmailType::class)
            ->add('phone')
            ->add('address')
            ->add('message')
            ->add('submit',SubmitType::class,[
                'label' => 'إرسال'
            ]);
        ;
    }



    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Message::class,
        ));
    }
}