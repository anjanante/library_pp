<?php

namespace App\DataFixtures;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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
            $manager->persist($book);
        }
        $manager->flush();
    }
}
