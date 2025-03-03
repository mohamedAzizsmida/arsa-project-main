<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\CategorieProduit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;


class AddProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nom', null, [
            'required' => false,
            'constraints' => [
                new NotBlank([
                    'message' => 'Le nom ne peut pas être vide.',
                ]),
                new Length([
                    'min' => 5,
                    'max' => 255,
                    'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                ]),
                new Regex([
                    'pattern' => '/^(?!.*[\$\@\#\%\&\*\!])[\p{L}0-9\s]+$/u',
                    'message' => 'Le nom ne doit pas contenir de caractères spéciaux comme $, @, #, etc.',
                ])
                
                
            ],
        ])
        ->add('quantite', IntegerType::class, [
            'label' => 'Quantité',
            'required' => false,
            'constraints' => [
                new NotBlank([
                    'message' => 'La quantité ne peut pas être vide.',
                ]),new Positive([
                    'message' => 'La quantité doit être un nombre positif.',
                ])
            ],
        ])
        ->add('descriptionProduit', null, [
            'label' => 'Description',
            'required' => false,
            'constraints' => [
                new NotBlank([
                    'message' => 'La description ne peut pas être vide.',
                ]),
                new Length([
                    'min' => 5,
                    'max' => 255,
                    'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                    'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                ])
            ],
        ])
        ->add('categorie', EntityType::class, [
            'label' => 'Catégorie',
            'required' => false,
            'class' => CategorieProduit::class, // This links the form field to the CategorieProduit entity
            'choice_label' => 'nom', // Display the name of the category
            'placeholder' => 'Sélectionner une catégorie',
            'constraints' => [
                new NotBlank([
                    'message' => 'Veuillez sélectionner une catégorie.',
                ])
            ],
        ]) 
        ->add('image', FileType::class, [
            'label' => 'Image du produit (JPEG, PNG)',
            'required' => false,
            'mapped' => false, // Cela signifie qu'il ne sera pas lié à une propriété de l'entité
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\File([
                    'maxSize' => '5M',
                    'mimeTypes' => ['image/jpeg', 'image/png'],
                    'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG).',
                ])
            ],
        ]);
    
        
    }
 

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
            'categories' => [], 
        ]);
    }
}
