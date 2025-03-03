<?php

namespace App\Form;

use App\Entity\Formation;
use App\Entity\Association;
use App\Entity\Cour;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la formation',
                'required' => true,
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => true,
            ])
            ->add('formateur', TextType::class, [
                'label' => 'Nom du formateur',
                'required' => true,
            ])
            ->add('date_debut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('association', EntityType::class, [
                'class' => Association::class,
                'choice_label' => 'name',
                'label' => 'Association liée',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
