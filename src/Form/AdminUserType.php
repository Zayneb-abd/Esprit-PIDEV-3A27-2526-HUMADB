<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('nom', TextType::class, [
                'label'       => 'Nom',
                'constraints' => [new NotBlank()],
            ])
            ->add('prenom', TextType::class, [
                'label'       => 'Prénom',
                'constraints' => [new NotBlank()],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Email',
                'constraints' => [new NotBlank()],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label'    => $isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas changer)' : 'Mot de passe',
                'mapped'   => false,
                'required' => !$isEdit,
            ])
            ->add('role', ChoiceType::class, [
                'label'   => 'Rôle',
                'choices' => [
                    'Administrateur RH' => 'ADMIN_RH',
                    'Manager'           => 'MANAGER',
                    'Employé'           => 'EMPLOYE',
                    'Candidat'          => 'CANDIDAT',
                ],
                'placeholder' => 'Choisir un rôle',
            ])
            ->add('date_naissance', DateType::class, [
                'label'    => 'Date de naissance',
                'widget'   => 'single_text',
                'required' => false,
            ])
            ->add('reputation_score', IntegerType::class, [
                'label'    => 'Score de réputation',
                'required' => false,
                'data'     => $options['data'] instanceof User ? $options['data']->getReputationScore() : 0,
            ])
            ->add('faceImageFile', FileType::class, [
                'label'       => 'Photo de profil (optionnel)',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
    }
}
