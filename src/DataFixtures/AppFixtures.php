<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    // Injection du password hasher via constructeur
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setNom('Utilisateur');
        $user->setRoles(['ROLE_USER']);

        // Hashage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'monmotdepasse');
        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();
    }
}
