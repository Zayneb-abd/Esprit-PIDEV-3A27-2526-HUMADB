<?php

namespace App\Form;

use App\Entity\Formation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sujet', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Sujet'
            ])
            ->add('formateur', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Formateur'
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'En ligne' => 'En ligne',
                    'Présentiel' => 'Présentiel',
                    'Hybride' => 'Hybride',
                ],
                'attr' => ['class' => 'form-control'],
                'label' => 'Type'
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
                'label' => 'Date de début',
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de début ne peut pas être déjà passée.',
                        'groups' => ['creation']
                    ])
                ]
            ])
            ->add('duree', IntegerType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1
                ],
                'label' => 'Durée (en jours)'
            ])
            ->add('localisation', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Localisation'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
