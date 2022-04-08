<?php

namespace App\Controller\Admin;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use App\Service\Slug;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class PostsController extends AbstractController
{
    #[Route('/posts', name: 'admin_posts')]
    public function index(PostRepository $postRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User essaye d\'accéder à une page avec un rôle non attribué !');
        $posts = $postRepository->findAll();
        return $this->render('admin/posts/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/posts/{id}', name: 'admin_posts_update', requirements: ['id' => '\d+'])]
    public function update($id, Request $request, ManagerRegistry $doctrine, PostRepository $postRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User essaye d\'accéder à une page avec un rôle non attribué !');
        $post = $postRepository->find($id);
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $doctrine->getManager();
            $em->persist($post);
            $em->flush();

            $this->addFlash(
                'success',                
                'Le post a été modifié !'
            );

            return $this->redirectToRoute('admin_posts');
        }

        return $this->render('posts/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/posts/remove/{id}', name: 'admin_posts_remove', requirements: ['id' => '\d+'])]
    public function remove($id, PostRepository $postRepository, Request $request, ManagerRegistry $doctrine): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User essaye d\'accéder à une page avec un rôle non attribué !');
        $post = $postRepository->find($id);
        $em = $doctrine->getManager();

        $categories = $post->getCategories()->getValues();
        if(!empty($categories)){
            foreach($categories as $category){
                $post->removeCategory($category);
            }
        }
        $em->remove($post);
        $em->flush();

        $this->addFlash(
            'success',
            'Le post a été supprimé !'
        );

        return $this->redirectToRoute('admin_posts');
    }
}