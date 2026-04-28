<?php

namespace App\Form;

use App\Entity\Entretien;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntretienType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('manager', EntityType::class, [
                'class' => User::class,
                'label' => 'Manager invite',
                'required' => true,
                'placeholder' => 'Selectionner le manager qui rejoindra l entretien',
                'query_builder' => static function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->andWhere('u.role = :role')
                        ->setParameter('role', 'MANAGER')
                        ->orderBy('u.prenom', 'ASC')
                        ->addOrderBy('u.nom', 'ASC');
                },
                'choice_label' => static function (User $user): string {
                    return trim(sprintf('%s %s - %s', $user->getPrenom(), $user->getNom(), $user->getEmail()));
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('date_entretien', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('duree_minutes', IntegerType::class, [
                'label' => 'Duree (minutes)',
                'empty_data' => '45',
                'attr' => ['class' => 'form-control', 'min' => 15, 'step' => 15],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Planifie' => 'PLANIFIE',
                    'Reporte' => 'REPORTE',
                    'Termine' => 'TERMINE',
                    'Annule' => 'ANNULE',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entretien::class,
        ]);
    }
}
