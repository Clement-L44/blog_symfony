<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentType;
use App\Form\PostType;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Service\FileUploader;
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
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        $user = $this->getUser();
        $posts = $postRepository->findBy(['user' => $user]);
        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/new', name: 'app_post_new')]
    public function new(Request $request, ManagerRegistry $doctrine, Slug $slug, FileUploader $fileUploader): Response
    {
        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }

        $user = $this->getUser();
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $doctrine->getManager();
            $file = $form->get('image')->getData();
            if($file){
                $fileName = $fileUploader->upload($file);
                $post->setImage($fileName);
            }
            $post->setUser($user);
            $post->setDateCreate(new DateTime('now'));
            $post->setSlug($slug->createSlug($post->getTitle(), '-'));
            foreach($post->getCategories()->getValues() as $category){
                $post->addCategory($category);
            }
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

    #[Route('/post/update/{id}', name: 'app_post_update', requirements: ['id' => '\d+'])]
    public function update($id, Request $request, ManagerRegistry $doctrine, PostRepository $postRepository): Response
    {
        $post = $postRepository->find($id);

        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if($post->getUser() != $this->getUser()){
            $this->addFlash(
                'notice',
                'Le post ne vous appartien pas, vous ne pouvez le modifier !'
            );
            return $this->redirectToRoute('app_post');
        }
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

    #[Route('/post/show/{id}', name: 'app_post_show', requirements: ['id' => '\d+'])]
    public function show($id, Request $request, ManagerRegistry $doctrine, PostRepository $postRepository): Response
    {
        $post = $postRepository->find($id);

        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $doctrine->getManager();
            $comment->setDateCreate(new DateTime('now'));
            $post->addComment($comment);
            $comment->setUser($this->getUser());
            $em->persist($comment, $post);
            $em->flush();

            $this->addFlash(
                'success',
                'Le commentaire a été ajouté au post '.$post->getTitle().' !'
            );

            return $this->redirectToRoute('app_post_show', ['id' => $id]);
        }

        return $this->render('post/show.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/post/remove/{id}', name: 'app_post_remove', requirements: ['id' => '\d+'])]
    public function remove($id, PostRepository $postRepository, Request $request, ManagerRegistry $doctrine): Response
    {
        $post = $postRepository->find($id);

        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if($post->getUser() != $this->getUser()){
            $this->addFlash(
                'notice',
                'Le post ne vous appartien pas, vous ne pouvez le supprimer !'
            );
            return $this->redirectToRoute('app_post');
        }

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

    #[Route('/post/comment/{id}/remove', name: 'app_post_comment_remove', requirements: ['id' => '\d+'])]
    public function remove_comment($id, CommentRepository $commentRepository, Request $request, ManagerRegistry $doctrine): Response
    {
        $comment = $commentRepository->find($id);

        if(!$this->getUser()){
            return $this->redirectToRoute('app_login');
        }
        if($comment->getUser() != $this->getUser()){
            $this->addFlash(
                'notice',
                'Le post ne vous appartien pas, vous ne pouvez le supprimer !'
            );
            return $this->redirectToRoute('app_post');
        }

        $em = $doctrine->getManager();
        $em->remove($comment);
        $em->flush();

        $this->addFlash(
            'success',
            'Le commentaire a été supprimé !'
        );

        return $this->redirectToRoute('app_post');
    }
}