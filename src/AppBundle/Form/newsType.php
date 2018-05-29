<?php

namespace AppBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class newsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class,[
                'label'=>'عنوان الخبر: '
            ])
            ->add('location', TextType::class,[
                'label'=>'مكان الخبر: '
            ])
            ->add('post',TextareaType::class,[
                'label'=>'نص الخبر: '
            ])
            ->add('mainImage',FileType::class, [
                'label' => 'الصورة الأساسية: ',
                'required' => true,
                'data_class' => null
            ])
            ->add('secondImage',FileType::class, [
                'label' => 'الصورة الثانية: ',
                'required' => false,
                'data_class' => null
            ])
            ->add('isHot',CheckboxType::class,[
                'label'=>'خبر عاجل؟ ',
                'value'=>false,
                'required'=>false
            ])
            ->add('tag', TextType::class,[
                'label'=>'الوسوم (افصل بينها بفراغ): ',
                'required'=>false
            ])
            ->add('type',EntityType::class,[
                'class' => 'AppBundle:type',
                'choice_label' => 'name',
                'expanded' => true,
                'label' => 'نوع الخبر: ',
                'required' => true
            ]);
    }
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\news'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'appbundle_news';
    }


}
