<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => [
                    'autofocus' => true
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Les mots de passe saisis ne correspondent pas',
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'label_attr' => [
                        'title' => 'Pour des raisons de sécurité, votre mot de passe doit contenir au minimum 8 caractères dont une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial'
                    ],
                    'attr' => [
                        'pattern' => '^(?=.*[a-zà-ÿ])(?=.*[A-ZÀ-Ý])(?=.*[0-9])(?=.*[^a-zà-ÿA-ZÀ-Ý0-9]).{8,}$',
                        'title' => 'Pour des raisons de sécurité, votre mot de passe doit contenir au minimum 8 caractères dont une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial',
                        'maxlength' => 255
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'label_attr' => [
                        'title' => 'Confirmer le mot de passe'
                    ],
                    'attr' => [
                        'pattern' => '^(?=.*[a-zà-ÿ])(?=.*[A-ZÀ-Ý])(?=.*[0-9])(?=.*[^a-zà-ÿA-ZÀ-Ý0-9]).{8,}$',
                        'title' => 'Confirmer le mot de passe',
                        'maxlength' => 255
                    ]
                ]
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Accepter les conditions d\'utilisation',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation de ce site pour vous inscrire',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
