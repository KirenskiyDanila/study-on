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
use Symfony\Contracts\Translation\TranslatorInterface;

class UserRegistrationFormType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Электронная почта',
                'attr' => [
                    'class' => 'form-control mb-3',
                    'placeholder' => $this->translator->trans(
                        'placeholders.email',
                        [],
                        'messages'
                    ),
                ],
                'constraints' => [
                    new Email([
                    'message' => $this->translator->trans(
                        'errors.register.email.format',
                        [],
                        'messages'
                    ),
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
                        'message' => $this->translator->trans(
                            'errors.register.agreeTerms.isTrue',
                            [],
                            'messages'
                        ),
                    ]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => $this->translator->trans(
                    'errors.register.password.repeat',
                    [],
                    'messages'
                ),
                'mapped' => false,
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => [
                    'label' => 'Пароль',
                    'attr' =>
                        [
                        'autocomplete' => 'new-password',
                        'class' => 'form-control mb-3',
                        'placeholder' => $this->translator->trans(
                            'placeholders.firstPassword',
                            [],
                            'messages'
                        )],
                        'constraints' => [
                            new NotBlank([
                                'message' => $this->translator->trans(
                                    'errors.register.password.blank',
                                    [],
                                    'messages'
                                ),
                            ]),
                            new Length([
                                'min' => 6,
                                'minMessage' => $this->translator->trans(
                                    'errors.register.password.minLength',
                                    [],
                                    'messages'
                                ),
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
                        'placeholder' => $this->translator->trans(
                            'placeholders.secondPassword',
                            [],
                            'messages'
                        ) ],
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
