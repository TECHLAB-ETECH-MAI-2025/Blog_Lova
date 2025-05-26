<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\ArticleLike;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LikeController extends AbstractController
{
    #[Route('/article/{id}/like', name: 'article_like', methods: ['POST'])]
    public function like(Article $article, EntityManagerInterface $em, Request $request): JsonResponse
    {
        // Vérifie que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        // Recherche s'il a déjà liké
        $like = $em->getRepository(ArticleLike::class)->findOneBy([
            'article' => $article,
            'user' => $user,
        ]);

        if ($like) {
            // Si déjà liké, on supprime
            $em->remove($like);
            $em->flush();

            $likesCount = $em->getRepository(ArticleLike::class)->count(['article' => $article]);

            return new JsonResponse([
                'liked' => false,
                'likesCount' => $likesCount,
            ]);
        }

        // Si pas encore liké, on ajoute
        $newLike = new ArticleLike();
        $newLike->setArticle($article);
        $newLike->setUser($user);
        $newLike->setCreatedAt(new \DateTimeImmutable());

        $em->persist($newLike);
        $em->flush();

        $likesCount = $em->getRepository(ArticleLike::class)->count(['article' => $article]);

        return new JsonResponse([
            'liked' => true,
            'likesCount' => $likesCount,
        ]);
    }
}
