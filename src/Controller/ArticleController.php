<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\CommentaireType;
use App\Entity\Commentaire;

#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findAll();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            // On ne crée plus ici de formulaire, on fait ça dans Twig via un render partial (optionnel)
        ]);
    }

    #[Route('/{id}/comment', name: 'app_article_comment_new', methods: ['POST'])]
    public function newComment(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaire();
        $commentaire->setArticle($article);
        $commentaire->setAuteur($this->getUser());

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($commentaire);
                $entityManager->flush();

                return $this->json([
                    'success' => true,
                    'commentaire' => [
                        'auteur' => $commentaire->getAuteur()->getNom(), // adapte selon ta méthode
                        'contenu' => $commentaire->getContenu(),
                    ],
                ]);
            }

            return $this->json([
                'success' => false,
                'message' => 'Formulaire invalide'
            ], 400);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajouté.');
        } else {
            $this->addFlash('error', 'Erreur lors de l\'ajout du commentaire.');
        }

        return $this->redirectToRoute('app_article_index');
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setCreatedAt(new \DateTimeImmutable());
            $article->setAuteur($this->getUser());

            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index');
        }

        return $this->renderForm('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($article->getAuteur() === null && $this->getUser()) {
                $article->setAuteur($this->getUser());
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_article_index');
        }

        return $this->renderForm('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_article_index');
    }

    #[Route('/ajax/list', name: 'app_article_ajax_list', methods: ['GET'])]
    public function ajaxList(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();

        $data = [];

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'titre' => $article->getTitre(),
                'contenu' => mb_substr($article->getContenu(), 0, 100) . '...',
                'auteur' => $article->getAuteur()?->getNom() ?? 'Inconnu',
                'date' => $article->getCreatedAt()?->format('Y-m-d H:i') ?? '',
                'actions' => $this->renderView('article/_actions.html.twig', [
                    'article' => $article
                ])
            ];
        }

        return new JsonResponse(['data' => $data]);
    }
}
