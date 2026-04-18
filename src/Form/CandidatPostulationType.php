<?php

namespace App\Form;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class CandidatPostulationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['include_offer']) {
            $builder->add('offreEmploi', EntityType::class, [
                'class' => OffreEmploi::class,
                'label' => "Offre d'emploi",
                'choice_label' => static function (OffreEmploi $offre): string {
                    return trim(sprintf('%s - %s', $offre->getTitre(), $offre->getDepartement()));
                },
                'placeholder' => 'Choisir une offre',
                'attr' => ['class' => 'form-select'],
            ]);
        }

        $constraints = [
            new File([
                'maxSize' => '5M',
                'mimeTypes' => ['application/pdf'],
                'mimeTypesMessage' => 'Veuillez uploader un CV au format PDF.',
            ]),
        ];

        if ($options['require_cv']) {
            array_unshift($constraints, new NotNull([
                'message' => 'Le CV PDF est obligatoire pour postuler.',
            ]));
        }

        $builder->add('cvFile', FileType::class, [
            'label' => 'CV PDF',
            'mapped' => false,
            'required' => $options['require_cv'],
            'constraints' => $constraints,
            'attr' => ['class' => 'form-control', 'accept' => 'application/pdf'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidature::class,
            'include_offer' => true,
            'require_cv' => true,
        ]);

        $resolver->setAllowedTypes('include_offer', 'bool');
        $resolver->setAllowedTypes('require_cv', 'bool');
    }
}
