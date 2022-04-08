<?php

namespace App\Controller;

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

class PostController extends AbstractController
{
    #[Route('/post', name: 'app_post')]
    public function index(PostRepository $postRepository): Response
    {
        $user = $this->getUser();
        $posts = $postRepository->findBy(['user' => $user]);
        //dd($posts);
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/new', name: 'app_post_new')]
    public function new(Request $request, ManagerRegistry $doctrine, Slug $slug): Response
    {
        $user = $this->getUser();
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $doctrine->getManager();
            $post->setUser($user);
            $post->setDateCreate(new DateTime('now'));
            $post->setSlug($slug->createSlug($post->getTitle(), '-'));
            foreach($post->getCategories()->getValues() as $category){
                $post->addCategory($category);
            }
            //dd($post);
            $em->persist($post);
            $em->flush();

            $this->addFlash(
                'success',
                'Post enregistré !'  
            );
        }

        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/{id}', name: 'app_post_update', requirements: ['id' => '\d+'])]
    public function update($id, Request $request, ManagerRegistry $doctrine, PostRepository $postRepository): Response
    {

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

            return $this->redirectToRoute('app_post');
        }

        return $this->render('post/update.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/post/remove/{id}', name: 'app_post_remove', requirements: ['id' => '\d+'])]
    public function remove($id, PostRepository $postRepository, Request $request, ManagerRegistry $doctrine): Response
    {
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

        return $this->redirectToRoute('app_post');
    }
}