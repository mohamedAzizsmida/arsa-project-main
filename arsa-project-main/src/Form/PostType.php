<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormInterface; // ✅ Import nécessaire

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu')
            ->add('date_publication', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime',
                'required' => true,
                'attr' => ['class' => 'datepicker'],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPEG, PNG, GIF)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
            'validation_groups' => function (FormInterface $form) {
                return $form->getData()->getId() === null ? ['Default', 'create'] : ['Default'];
            },
        ]);
    }
}
