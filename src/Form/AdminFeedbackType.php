<?php

namespace App\Form;

use App\Entity\Feedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Statut du Feedback',
                'choices' => [
                    'Nouveau' => 'nouveau',
                    'En cours de traitement' => 'en_cours',
                    'Traité' => 'traite',
                    'Rejeté' => 'rejete'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie Actuelle',
                'choices' => [
                    'Soutien technique' => 'Soutien technique',
                    'Suggestion' => 'Suggestion',
                    'Plainte' => 'Plainte',
                    'Culture de l\'entreprise' => 'Culture de l\'entreprise',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
        ]);
    }
}
