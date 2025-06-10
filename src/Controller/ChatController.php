<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Form\MessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ChatController extends AbstractController
{
    /**
     * Page de chat avec un utilisateur donné
     * 
     * @Route("/chat/{id}", name="app_chat")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function chat(User $receiver, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($user);
            $message->setReceiver($receiver);
            $message->setCreatedAt(new \DateTime());

            $em->persist($message);
            $em->flush();

            return $this->redirectToRoute('app_chat', ['id' => $receiver->getId()]);
        }

        $messages = $em->getRepository(Message::class)->createQueryBuilder('m')
            ->where('(m.sender = :user AND m.receiver = :receiver) OR (m.sender = :receiver AND m.receiver = :user)')
            ->setParameter('user', $user)
            ->setParameter('receiver', $receiver)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('chat/index.html.twig', [
            'receiver' => $receiver,
            'messages' => $messages,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Page d’accueil du chat (liste des utilisateurs avec qui chatter)
     * 
     * @Route("/chat", name="app_chat_home")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function home(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $users = $em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $user)
            ->getQuery()
            ->getResult();

        return $this->render('chat/home.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * Récupération des messages en JSON (AJAX)
     * 
     * @Route("/chat/messages/{id}", name="app_chat_messages", methods={"GET"})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
 public function getMessages(User $receiver, EntityManagerInterface $em): JsonResponse
 {
    $user = $this->getUser();

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

        // Si le message a été envoyé il y a moins de 5 minutes (300 secondes)
        if ($interval < 120) {
            $createdAtFormatted = "à l'instant";
        } else {
            $createdAtFormatted = $createdAt->format('d/m/Y H:i');
        }

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

    /**
     * @Route("/chat/send/{id}", name="app_chat_send", methods={"POST"})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function sendMessage(User $receiver, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        // Récupération du contenu JSON brut
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
        'createdAt' => "à l'instant", // directement à l'instant pour un message fraîchement envoyé
     ]);

    }
}