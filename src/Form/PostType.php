<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Post;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Util\TestDox\TextResultPrinter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre'
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu'
            ])
            ->add('image', UrlType::class,[
                'label' => 'Enregistrer une image',
                'required' => false,
            ])
            ->add('categories', EntityType::class, [
                'class' => Category::class,
                'label' => 'Catégorie(s)',
                'choice_label' => 'title',
                'multiple' => true,
                'by_reference' => false,
            ]) 
            ->add('submit', SubmitType::class, [
                'label' => 'Valider'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
