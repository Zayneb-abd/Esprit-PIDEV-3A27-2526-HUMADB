<?php

namespace App\Form;

use App\Entity\Feedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Soutien technique' => 'Soutien technique',
                    'Suggestion' => 'Suggestion',
                    'Plainte' => 'Plainte',
                    'Culture de l\'entreprise' => 'Culture de l\'entreprise',
                    'Autre' => 'Autre',
                ],
                'placeholder' => 'Sélectionnez une catégorie',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez choisir une catégorie.'])
                ]
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => ['rows' => 5, 'placeholder' => 'Décrivez votre feedback en détail...'],
                'constraints' => [
                    new NotBlank(['message' => 'Le contenu du feedback ne peut pas être vide.']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères.',
                        'max' => 2000,
                        'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ]
            ])
            ->add('est_anonyme', CheckboxType::class, [
                'label' => 'Soumettre de manière anonyme',
                'required' => false,
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
