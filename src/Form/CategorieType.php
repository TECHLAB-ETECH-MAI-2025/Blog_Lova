<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Article;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Entrez une catégorie',
                ],
            ])
            ->add('articles', EntityType::class, [
                'class' => Article::class,
                'choice_label' => 'titre',
                'label' => 'Articles associés',
                'multiple' => true,
                'expanded' => false, // true pour checkboxes, false pour select multiple
                'attr' => ['class' => 'form-select'],
                'required' => false,
                'placeholder' => 'Sélectionnez des articles',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
        ]);
    }
}
