<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Unique;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'required' => true,
                'label' => 'Символьный код',
                'constraints' => [new Length(['max'=>255]),
                    new NotBlank(['message' => 'Поле не должно быть пустым!'])],
                'attr' => ['class ' => 'form-control']
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Название',
                'constraints' => [new Length(['min' => 3, 'max'=>255]),
                    new NotBlank(['message' => 'Поле не должно быть пустым!'])],
                'attr' => ['class ' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'attr' => ['class ' => 'form-control mb-2']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
