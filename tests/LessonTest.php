<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LessonTest extends AbstractTest
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function setUpClient()
    {
        self::getClient()->disableReboot();

        self::getClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock(self::getClient()->getContainer()->get(TokenStorageInterface::class))
        );

        return self::getClient();
    }

    /**
     * @throws Exception
     */
    public function testGetMethods(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
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

    /**
     * @throws Exception
     */
    public function test404Methods(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
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

    /**
     * @throws Exception
     */
    public function testGetCourseMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId());
            $this->assertEquals($lesson->getContent(), $crawler->filter('.h5')->innerText());
        }
    }


    /**
     * @throws Exception
     */
    public function testAddLessonMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $id = $course->getId();
        self::getClient()->request('GET', '/lessons/new/' . $id);
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
        self::assertSame(
            'This value is too short. It should have 3 characters or more.',
            $crawler->filter('li')->text()
        );

        self::getClient()->submitForm('Добавить', [
            'lesson[name]' => '   ',
            'lesson[content]' => '123456',
            'lesson[serialNumber]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame(
            'Поле не должно быть пустым!',
            $crawler->filter('li')->text()
        );


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


    /**
     * @throws Exception
     */
    public function testEditLessonMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lesson = $course->getLessons()[0];
        self::getClient()->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Редактировать', [
            'lesson[name]' => '12',
            'lesson[content]' => '123456',
            'lesson[serialNumber]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame(
            'This value is too short. It should have 3 characters or more.',
            $crawler->filter('li')->text()
        );
        self::getClient()->submitForm('Редактировать', [
            'lesson[name]' => '123456',
            'lesson[content]' => '123456',
            'lesson[serialNumber]' => '12345678'
        ]);
        $this->assertResponseCode(303);
        self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::getClient()->getCrawler();
        self::assertPageTitleContains('123456 / '.$course->getName() .' / StudyOn');
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $lessons = $course->getLessons();
        $highestNumber = 0;
        foreach ($lessons as $lesson) {
            if ($lesson->getSerialNumber() > $highestNumber) {
                $highestNumber = $lesson->getSerialNumber();
            }
        }
        $this->assertEquals(count($course->getLessons()), $highestNumber);
    }

    /**
     * @throws Exception
     */
    public function testDeleteLessonMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $count = count($course->getLessons());
        $lesson = $course->getLessons()[0];
        self::getClient()->request('GET', '/lessons/' . $lesson->getId());
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Удалить', []);
        $this->assertResponseCode(303);
        self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains($course->getName() . ' / StudyOn');
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $this->assertEquals(count($course->getLessons()), $count - 1);
    }

    /**
     * @throws Exception
     */
    public function testPostMethods(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
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
