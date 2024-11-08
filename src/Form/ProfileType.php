<?php

namespace App\Form;

use App\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped'=> false,
                'first_options' => ['label' => 'Mot de passe', 'required' => false, 'empty_data' => ''],
                'second_options' => ['label' => 'Confirmer le mot de passe', 'required' => false, 'empty_data' => ''],
                'required' => false,
                'empty_data' => '',
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide',
                    ])
                ],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
        ]);
    }
}