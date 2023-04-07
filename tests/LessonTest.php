<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;

class LessonTest extends AbstractTest
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetMethods(): void
    {
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();

        $urls = [];

        foreach ($lessons as $lesson) {
            $urls[] = '/lessons/new/' . $lesson->getCourse()->getId();
            $urls[] = '/lessons/' . $lesson->getId() . '/edit';
            $urls[] = '/lessons/' . $lesson->getId();

        }

        foreach ($urls as $url) {
            self::getClient()->request('GET', $url);
            $this->assertResponseOk();
        }
    }

    public function test404Methods(): void
    {
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();

        $urls = [];

        foreach ($lessons as $lesson) {
            $urls[] = '/lessons/' . ($lesson->getId() + 20) . '/edit';
            $urls[] = '/lessons/' . ($lesson->getId() + 20);
        }

        foreach ($urls as $url) {
            self::getClient()->request('GET', $url);
            $this->assertResponseCode(404);
        }
    }

    public function testGetCourseMethod(): void
    {
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId());
            $this->assertEquals($lesson->getContent(), $crawler->filter('.h5')->innerText());
        }
    }


    public function testAddLessonMethod(): void
    {
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $id = $course->getId();
        $crawler = self::getClient()->request('GET', '/lessons/new/' . $id);
        $this->assertResponseCode(200);
        $count = count($course->getLessons());

        self::getClient()->submitForm('Добавить', [
            'lesson[name]' => '12',
            'lesson[content]' => '123456',
            'lesson[serialNumber]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::getClient()->submitForm('Добавить', [
            'lesson[name]' => '123456',
            'lesson[content]' => '123456',
            'lesson[serialNumber]' => '12345678'
        ]);
        $this->assertResponseCode(303);
        $crawler = self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains($course->getName() .' / StudyOn');
        $this->assertCount($count + 1, $crawler->filter('.list-group-item'));
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lessons = $course->getLessons();
        $highestNumber = 0;
        foreach ($lessons as $lesson) {
            if ($lesson->getSerialNumber() > $highestNumber) {
                $highestNumber = $lesson->getSerialNumber();
            }
        }
        // проверка на изменение порядкового номера с 12345678 на count + 1
        $this->assertEquals($count + 1, $highestNumber);
    }


    public function testEditLessonMethod(): void
    {
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lesson = $course->getLessons()[0];
        $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Редактировать', [
            'lesson[name]' => '12',
            'lesson[content]' => '123456',
            'lesson[serialNumber]' => '12345678'
        ]);

        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::getClient()->submitForm('Редактировать', [
            'lesson[name]' => '123456',
            'lesson[content]' => '123456',
            'lesson[serialNumber]' => '12345678'
        ]);
        $this->assertResponseCode(303);
        $crawler = self::getClient()->followRedirect();
        $this->assertResponseOk();
        $crawler = self::getClient()->getCrawler();
        self::assertPageTitleContains( '123456 / '.$course->getName() .' / StudyOn');
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lessons = $course->getLessons();
        $highestNumber = 0;
        foreach ($lessons as $lesson) {
            if ($lesson->getSerialNumber() > $highestNumber) {
                $highestNumber = $lesson->getSerialNumber();
            }
        }
        // проверка на изменение порядкового номера с 12345678 на count + 1
        $this->assertEquals(count($course->getLessons()), $highestNumber);
    }

    public function testDeleteLessonMethod(): void
    {
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $count = count($course->getLessons());
        $lesson = $course->getLessons()[0];
        $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId());
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Удалить', []);
        $this->assertResponseCode(303);
        $crawler = self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains( $course->getName() . ' / StudyOn');
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $this->assertEquals(count($course->getLessons()), $count - 1);
    }

    public function testPostMethods(): void
    {
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();

        $urls = [];

        foreach ($lessons as $lesson) {
            $urls[] = '/lessons/new/' . $lesson->getCourse()->getId();
            $urls[] = '/lessons/' . $lesson->getId() . '/edit';
        }

        foreach ($urls as $url) {
            self::getClient()->request('POST', $url);
            $this->assertResponseOk();
        }

        $urls = [];
        foreach ($lessons as $lesson) {
            $urls[] = '/lessons/' . $lesson->getId();

        }

        foreach ($urls as $url) {
            self::getClient()->request('POST', $url);
            $this->assertResponseRedirect();
        }
    }
}