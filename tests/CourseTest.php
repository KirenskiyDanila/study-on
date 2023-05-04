<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Exception;

class CourseTest extends AbstractTest
{

    public function setUp(): void
    {
        parent::setUp();
    }

    public function setUpClient()
    {
//        self::getClient()->disableReboot();
//
//        self::getClient()->getContainer()->set(
//            BillingClient::class,
//            new BillingClientMock('')
//        );

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
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();

        $urls = ['/courses/',
            '/courses/new'];

        foreach ($courses as $course) {
            $urls[] = '/courses/' . $course->getId();
            $urls[] = '/courses/' . $course->getId() . '/edit';
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
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();

        foreach ($courses as $course) {
            $urls[] = '/courses/' . ($course->getId() + 5);
            $urls[] = '/courses/' . ($course->getId() + 5) . '/edit';
        }

        foreach ($urls as $url) {
            self::getClient()->request('GET', $url);
            $this->assertResponseCode(404);
        }
    }

    /**
     * @throws Exception
     */
    public function testIndexCourseMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $crawler = self::getClient()->request('GET', '/courses/');
        $this->assertCount(count($courses), $crawler->filter('.card'));
    }

    /**
     * @throws Exception
     */
    public function testGetCourseMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            $crawler = self::getClient()->request('GET', '/courses/' . $course->getId());
            $this->assertCount(count($course->getLessons()), $crawler->filter('.list-group-item'));
        }
    }

    /**
     * @throws Exception
     */
    public function testAddCourseMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $total = count($courses);
        self::authorizeAdmin($crawler, $client, $this);
        $crawler = self::getClient()->request('GET', '/courses/');
        self::getClient()->click($crawler->filter('.btn')->selectLink('Создать новый курс')->link());
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Добавить', [
            'course[code]' => '   ',
            'course[name]' => '123456',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame(
            'Поле не должно быть пустым!',
            $crawler->filter('li')->text()
        );

        $crawler = self::getClient()->request('GET', '/courses/');
        self::getClient()->click($crawler->filter('.btn')->selectLink('Создать новый курс')->link());
        $this->assertResponseCode(200);
        self::getClient()->submitForm('Добавить', [
            'course[code]' => '1231231231232132132',
            'course[name]' => '   ',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame(
            'Поле не должно быть пустым!',
            $crawler->filter('li')->text()
        );

        $crawler = self::getClient()->request('GET', '/courses/');
        self::getClient()->click($crawler->filter('.btn')->selectLink('Создать новый курс')->link());
        $this->assertResponseCode(200);
        self::getClient()->submitForm('Добавить', [
            'course[code]' => 'course-2',
            'course[name]' => '123456',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame('Поле символьного кода должно быть уникальным!', $crawler->filter('li')->text());

        self::getClient()->submitForm('Добавить', [
            'course[code]' => 'course-4',
            'course[name]' => '12',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame(
            'This value is too short. It should have 3 characters or more.',
            $crawler->filter('li')->text()
        );
        self::getClient()->submitForm('Добавить', [
            'course[code]' => 'course-5',
            'course[name]' => '123456',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(303);
        $crawler = self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains('Список курсов');
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $this->assertCount($total + 1, $courses);
        $this->assertCount(count($courses), $crawler->filter('.card'));
    }

    /**
     * @throws Exception
     */
    public function testEditCourseMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        self::getClient()->request('GET', '/courses/' . $course->getId() . '/edit');
        $this->assertResponseCode(200);
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $total = count($courses);

        self::getClient()->submitForm('Редактировать', [
            'course[code]' => 'course-2',
            'course[name]' => '123456',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame('Поле символьного кода должно быть уникальным!', $crawler->filter('li')->text());

        self::getClient()->submitForm('Редактировать', [
            'course[code]' => 'course-4',
            'course[name]' => '12',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        self::assertSame(
            'This value is too short. It should have 3 characters or more.',
            $crawler->filter('li')->text()
        );
        self::getClient()->submitForm('Редактировать', [
            'course[code]' => 'course-5',
            'course[name]' => '123456',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(303);
        self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains('123456 / StudyOn');

        $crawler = self::getClient()->request('GET', '/courses/');
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $this->assertCount($total, $courses);
        $this->assertCount(count($courses), $crawler->filter('.card'));
    }

    /**
     * @throws Exception
     */
    public function testDeleteCourseMethod(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $count = count(self::getEntityManager()->getRepository(Course::class)->findAll());
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        $id = $course->getId();
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findBy(['course' => $course]);
        self::assertCount(4, $lessons);
        self::getClient()->request('GET', '/courses/' . $course->getId());
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Удалить', []);
        $this->assertResponseCode(303);
        self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains('Список курсов / StudyOn');
        $newCount = count(self::getEntityManager()->getRepository(Course::class)->findAll());
        $this->assertEquals($newCount, $count - 1);

        $newLessons = self::getEntityManager()->getRepository(Lesson::class)->findBy(['course' => $course]);
        self::assertCount(0, $newLessons);
    }

    /**
     * @throws Exception
     */
    public function testPostMethods(): void
    {

        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();

        $urls = ['/courses/new'];

        foreach ($courses as $course) {
            $urls[] = '/courses/' . $course->getId() . '/edit';
        }

        foreach ($urls as $url) {
            self::getClient()->request('POST', $url);
            $this->assertResponseOk();
        }

        $urls = [];

        foreach ($courses as $course) {
            $urls[] = '/courses/' . $course->getId();
        }

        foreach ($urls as $url) {
            self::getClient()->request('POST', $url);
            $this->assertResponseRedirect();
        }
    }
}
