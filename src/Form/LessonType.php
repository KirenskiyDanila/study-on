<?php

namespace App\Form;

use App\Entity\Lesson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LessonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => true,
                'label' => 'Название',
                'constraints' => [new Length(['min' => 3, 'max'=>255]),
                    new NotBlank(['message' => 'Поле не должно быть пустым!'])],
                'attr' => ['class ' => 'form-control']
            ])
            ->add('content', TextareaType::class, [
                'required' => true,
                'label' => 'Содержимое',
                'constraints' => [new Length(['min' => 3, 'max'=>255]),
                    new NotBlank(['message' => 'Поле не должно быть пустым!'])],
                'attr' => ['class ' => 'form-control']
            ])
            ->add('serialNumber', IntegerType::class, [
                'required' => true,
                'label' => 'Порядковый номер',
                'attr' => ['class ' => 'form-control mb-2'],
                'constraints' => [
                    new NotBlank(['message' => 'Поле не должно быть пустым!'])],
            ])
            ->add('course', HiddenType::class, [
                'data' => null,
                'disabled' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}
