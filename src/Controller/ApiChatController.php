<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route('/api/chat')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ApiChatController extends AbstractController
{
    // Récupérer la liste des utilisateurs (autres que l'utilisateur connecté)
    #[Route('/users', name: 'api_chat_users', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        $users = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $user)
            ->getQuery()
            ->getResult();

        $data = array_map(function(User $u) {
            return [
                'id' => $u->getId(),
                'name' => $u->getNom(),
            ];
        }, $users);

        return new JsonResponse($data);
    }

    // Récupérer les messages entre l'utilisateur connecté et un destinataire donné
    #[Route('/messages/{receiverId}', name: 'api_chat_messages', methods: ['GET'])]
    public function getMessages(int $receiverId, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $receiver = $em->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            return new JsonResponse(['error' => 'Utilisateur destinataire non trouvé'], 404);
        }

        $messages = $em->getRepository(Message::class)->createQueryBuilder('m')
            ->where('(m.sender = :user AND m.receiver = :receiver) OR (m.sender = :receiver AND m.receiver = :user)')
            ->setParameter('user', $user)
            ->setParameter('receiver', $receiver)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $now = new \DateTime();
        $data = [];
        foreach ($messages as $message) {
            $createdAt = $message->getCreatedAt();
            $interval = $now->getTimestamp() - $createdAt->getTimestamp();

            $createdAtFormatted = $interval < 120 ? "à l'instant" : $createdAt->format('d/m/Y H:i');

            $data[] = [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'sender_id' => $message->getSender()->getId(),
                'sender_name' => $message->getSender()->getId() === $user->getId() ? 'Moi' : $message->getSender()->getNom(),
                'createdAt' => $createdAtFormatted,
            ];
        }

        return new JsonResponse($data);
    }

    // Envoyer un message à un utilisateur donné
    #[Route('/send/{receiverId}', name: 'api_chat_send', methods: ['POST'])]
    public function sendMessage(int $receiverId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $receiver = $em->getRepository(User::class)->find($receiverId);
        if (!$receiver) {
            return new JsonResponse(['error' => 'Utilisateur destinataire non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;

        if (!$content || trim($content) === '') {
            return new JsonResponse(['error' => 'Le message est vide'], 400);
        }

        $message = new Message();
        $message->setSender($user);
        $message->setReceiver($receiver);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTime());

        $em->persist($message);
        $em->flush();

        return new JsonResponse([
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'sender_id' => $user->getId(),
            'sender_name' => 'Moi',
            'createdAt' => "à l'instant",
        ]);
    }
}
