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
        $maxUploadSize = $this->getEffectiveMaxUploadSize();

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
                'maxSize' => $maxUploadSize,
                'mimeTypes' => ['application/pdf', 'application/x-pdf', 'application/acrobat', 'applications/vnd.pdf', 'text/pdf', 'text/x-pdf', 'application/octet-stream'],
                'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide.',
                'maxSizeMessage' => sprintf('Le CV depasse la taille maximale autorisee (%s).', $maxUploadSize),
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

    private function getEffectiveMaxUploadSize(): string
    {
        $appLimit = $this->toBytes('5M');
        $phpUploadLimit = $this->toBytes((string) ini_get('upload_max_filesize'));
        $phpPostLimit = $this->toBytes((string) ini_get('post_max_size'));

        $effectiveLimit = min(array_filter([$appLimit, $phpUploadLimit, $phpPostLimit]));

        return $this->formatBytes($effectiveLimit);
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        $unit = strtoupper(substr($value, -1));

        return match ($unit) {
            'G' => (int) ($number * 1024 * 1024 * 1024),
            'M' => (int) ($number * 1024 * 1024),
            'K' => (int) ($number * 1024),
            default => (int) $number,
        };
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return ((int) round($bytes / (1024 * 1024))).'M';
        }

        if ($bytes >= 1024) {
            return ((int) round($bytes / 1024)).'K';
        }

        return (string) $bytes;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidature::class,
            'include_offer' => true,
            'require_cv' => true,
            'validation_groups' => false,
        ]);

        $resolver->setAllowedTypes('include_offer', 'bool');
        $resolver->setAllowedTypes('require_cv', 'bool');
    }
}
