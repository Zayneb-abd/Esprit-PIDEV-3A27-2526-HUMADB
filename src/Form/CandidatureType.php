<?php

namespace App\Form;

use App\Entity\Candidature;
use App\Entity\OffreEmploi;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CandidatureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'label' => 'Candidat',
                'required' => false,
                'placeholder' => 'Choisir un candidat',
                'query_builder' => static function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->andWhere('u.role = :role')
                        ->setParameter('role', 'CANDIDAT')
                        ->orderBy('u.prenom', 'ASC')
                        ->addOrderBy('u.nom', 'ASC');
                },
                'choice_label' => static function (User $user): string {
                    return trim(sprintf('%s %s - %s', $user->getPrenom(), $user->getNom(), $user->getEmail()));
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('offreEmploi', EntityType::class, [
                'class' => OffreEmploi::class,
                'label' => "Offre d'emploi",
                'required' => false,
                'placeholder' => 'Choisir une offre',
                'choice_label' => static function (OffreEmploi $offre): string {
                    return trim(sprintf('%s - %s', $offre->getTitre(), $offre->getDepartement()));
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'choices' => [
                    'En attente' => 'En attente',
                    'En cours' => 'En cours',
                    'Acceptee' => 'Acceptee',
                    'Refusee' => 'Refusee',
                ],
                'placeholder' => 'Choisir un statut',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('cv', TextType::class, [
                'label' => 'CV',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du fichier ou lien vers le CV'],
            ])
            ->add('date_candidature', DateType::class, [
                'label' => 'Date de candidature',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('date_statut', DateType::class, [
                'label' => 'Date du statut',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Candidature::class,
        ]);
    }
}
