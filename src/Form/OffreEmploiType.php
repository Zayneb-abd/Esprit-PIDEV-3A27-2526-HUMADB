<?php

namespace App\Form;

use App\Entity\OffreEmploi;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffreEmploiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'attr' => ['class' => 'form-control', 'minlength' => 3, 'maxlength' => 100],
            ])
            ->add('departement', TextType::class, [
                'label' => 'Departement',
                'required' => true,
                'attr' => ['class' => 'form-control', 'minlength' => 2, 'maxlength' => 100],
            ])
            ->add('type_contrat', ChoiceType::class, [
                'label' => 'Type de contrat',
                'required' => true,
                'choices' => [
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'Stage' => 'Stage',
                    'Freelance' => 'Freelance',
                    'Alternance' => 'Alternance',
                ],
                'placeholder' => 'Choisir un type',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('date_publication', DateType::class, [
                'label' => 'Date de publication',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nombre_postes', IntegerType::class, [
                'label' => 'Nombre de postes',
                'required' => true,
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 1000],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'attr' => ['class' => 'form-control', 'rows' => 6, 'minlength' => 20, 'maxlength' => 5000],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OffreEmploi::class,
        ]);
    }
}
