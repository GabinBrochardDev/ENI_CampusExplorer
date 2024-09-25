<?php

namespace App\Form;

use App\Entity\Sortie;
use App\Entity\Campus;
use App\Entity\Lieu;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;  // Utilisation de EntityType pour les relations
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('nbInscriptionMax', IntegerType::class, [
                'label' => 'Nombre de places',
                'attr' => ['class' => 'form-control']
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez la durée en minutes']
            ])
            ->add('infosSortie', TextareaType::class, [
                'label' => 'Description et infos',
                'attr' => ['class' => 'form-control']
            ])
            ->add('campus', EntityType::class, [  // Ajout du champ campus
                'class' => Campus::class,
                'choice_label' => 'nom',  // Utiliser la propriété 'nom' pour les options
                'label' => 'Campus',
                'attr' => ['class' => 'form-control']
            ])
            ->add('lieu', EntityType::class, [  // Ajout du champ lieu
                'class' => Lieu::class,
                'choice_label' => 'nom',  // Utiliser la propriété 'nom' pour les options
                'label' => 'Lieu',
                'attr' => ['class' => 'block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,  // Associer ce formulaire à l'entité Sortie
        ]);
    }
}
