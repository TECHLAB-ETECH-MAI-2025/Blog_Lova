<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentaire;
use App\Form\ArticleType;
use App\Form\CommentaireType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{
    #[Route('/', name: 'blog_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Formulaire d'ajout d'article
        $article = new Article();
        $articleForm = $this->createForm(ArticleType::class, $article);
        $articleForm->handleRequest($request);

        if ($articleForm->isSubmitted() && $articleForm->isValid()) {
            $em->persist($article);
            $em->flush();
            $this->addFlash('success', 'Article enregistré avec succès !');
            return $this->redirectToRoute('blog_index');
        }

        // Récupération des articles
        $articles = $em->getRepository(Article::class)->findAll();

        // Création d'un formulaire de commentaire pour chaque article
        $commentForms = [];

        foreach ($articles as $articleItem) {
            $commentaire = new Commentaire();
            $commentaire->setArticle($articleItem);

            $commentForm = $this->createForm(CommentaireType::class, $commentaire);
            $commentForm->handleRequest($request);

            if (
                $commentForm->isSubmitted() &&
                $commentForm->isValid() &&
                $request->request->has($commentForm->getName())
            ) {
                // ✅ Ajout de l’auteur connecté
                $user = $this->getUser();
                if ($user === null) {
                    throw $this->createAccessDeniedException('Vous devez être connecté pour commenter.');
                }

                $commentaire->setAuteur($user);

                $em->persist($commentaire);
                $em->flush();

                $this->addFlash('success', 'Commentaire ajouté avec succès !');
                return $this->redirectToRoute('blog_index');
            }

            $commentForms[$articleItem->getId()] = $commentForm->createView();
        }

        return $this->render('blog/index.html.twig', [
            'articleForm' => $articleForm->createView(),
            'articles' => $articles,
            'commentForms' => $commentForms,
        ]);
    }
}
