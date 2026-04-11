<?php

namespace App\Form;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatPostulationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('offreEmploi', EntityType::class, [
                'class' => OffreEmploi::class,
                'label' => "Offre d'emploi",
                'choice_label' => static function (OffreEmploi $offre): string {
                    return trim(sprintf('%s - %s', $offre->getTitre(), $offre->getDepartement()));
                },
                'placeholder' => 'Choisir une offre',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('cv', TextType::class, [
                'label' => 'CV',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Lien ou nom du fichier CV',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidature::class,
        ]);
    }
}
