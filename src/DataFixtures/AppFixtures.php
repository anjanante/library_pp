<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AppFixtures extends Fixture
{
    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;

    }
    public function load(ObjectManager $manager): void
    {
        //create user "simple"
        $user = new User();
        $user->setEmail('user@library.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, 'password'));
        $manager->persist($user);
        //create user "admin"
        $userAdmin = new User();
        $userAdmin->setEmail('admin@library.com');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, 'password'));
        $manager->persist($userAdmin);
        //create author
        $aAuthor = [];
        for ($i=0; $i < 20 ; $i++) { 
            $author = new Author();
            $author->setFirstName('FAuthor '.$i);
            $author->setLastName('LAuthor '.$i);
            $manager->persist($author);
            $aAuthor[] = $author;
        }
        //create book
        for ($i=0; $i < 20 ; $i++) { 
            $book = new Book();
            $book->setTitle('Title '.$i);
            $book->setCoverText('Cover number '.$i);
            $book->setAuthor($aAuthor[array_rand($aAuthor)]);
            $book->setComment('Comment '.$i);
            $manager->persist($book);
        }
        $manager->flush();
    }
}
