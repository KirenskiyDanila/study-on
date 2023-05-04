<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Электронная почта',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => 'Введите вашу электронную почту...',
                ],
                'constraints' => [
                    new Email([
                    'message' => 'Электронная почта неправильно заполнена.',
        ]),
    ],
            ])
            ->add('agreeTerms', CheckboxType::class, [

                'mapped' => false,
                'attr' => [
                    'class' => 'form-input-check',
                    'checked' => '',
                    'autocomplete' => 'off'
                ],
                'constraints' => [
                    new IsTrue([
                        'message' => 'Вы должны согласиться с правилами пользования.',
                    ]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Пароли должны совпадать.',
                'mapped' => false,
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => [
                    'label' => 'Пароль',
                    'attr' =>
                        [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control mb-3',
                        'placeholder' => 'Введите ваш пароль...'],
                        'constraints' => [
                            new NotBlank([
                                'message' => 'Пожалуйста, введите пароль',
                            ]),
                            new Length([
                                'min' => 6,
                                'minMessage' => 'Ваш пароль должен состоять из минимум {{ limit }} символов',
                                // max length allowed by Symfony for security reasons
                                'max' => 4096,
                            ])
                            ]
                    ],
                'second_options' => [
                    'label' => 'Повторите пароль',
                    'attr' =>
                        [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control mb-3',
                        'placeholder' => 'Повторите пароль...' ],
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
