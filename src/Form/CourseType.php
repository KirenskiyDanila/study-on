<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Contracts\Translation\TranslatorInterface;

class CourseType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'required' => true,
                'label' => 'Символьный код',
                'constraints' => [new Length(['max'=>255,
                    'maxMessage' => $this->translator->trans(
                        'errors.course.code.length',
                        [],
                        'messages'
                    )]),
                    new NotBlank(['message' => $this->translator->trans(
                        'errors.course.code.blank',
                        [],
                        'messages'
                    )])],
                'attr' => ['class ' => 'form-control']
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'Название',
                'constraints' => [new Length(['min' => 3, 'max'=>255,
                    'minMessage' => $this->translator->trans(
                        'errors.course.title.minLength',
                        [],
                        'messages'
                    ),
                    'maxMessage' => $this->translator->trans(
                        'errors.course.title.maxLength',
                        [],
                        'messages'
                    )]),
                    new NotBlank(['message' => $this->translator->trans(
                        'errors.course.title.blank',
                        [],
                        'messages'
                    )])],
                'attr' => ['class ' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'attr' => ['class ' => 'form-control']])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Полный курс' => 'buy',
                    'Аренда курса' => 'rent',
                    'Бесплатный курс' => 'free'
                ],
                'invalid_message'=> $this->translator->trans(
                    'errors.course.type.choice',
                    [],
                    'messages'
                ),
                'required' => true,
                'mapped' => false,
                'label' => 'Тип оплаты',
                'attr' => ['class ' => 'form-control'],
                'constraints' => [new NotBlank([
                    'message' => $this->translator->trans(
                        'errors.course.type.blank',
                        [],
                        'messages'
                    )]),
                    new Choice([
                        'message' => $this->translator->trans(
                            'errors.course.type.choice',
                            [],
                            'messages'
                        ),
                        'choices' => ['buy', 'rent', 'free']
                        ])]
            ])
            ->add('price', NumberType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'Цена курса',
                'attr' => ['class ' => 'form-control mb-2'],
                'constraints' => [
                    new PositiveOrZero(['message' => $this->translator->trans(
                        'errors.course.price.positiveOrZero',
                        [],
                        'messages'
                    )])]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
