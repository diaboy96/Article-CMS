<?php

namespace App\Form;

use App\Entity\Login;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['attr' => ['placeholder' => 'jméno']])
            ->add('pass', PasswordType::class, ['attr' => ['placeholder' => 'heslo']])
            ->add('pass_again', PasswordType::class, ['attr' => ['placeholder' => 'heslo znovu']])
            ->add('email', EmailType::class, ['attr' => ['placeholder' => 'vas@email.cz']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Login::class,
        ]);
    }
}
