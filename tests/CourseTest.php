<?php

namespace App\Tests;

use App\Entity\Course;
use Exception;

class CourseTest extends AbstractTest
{

    public function setUp(): void
    {
        parent::setUp();
    }


    /**
     * @throws Exception
     */
    public function testGetMethods(): void
    {
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
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $crawler = self::getClient()->request('GET', '/courses/');
        $this->assertCount(count($courses), $crawler->filter('.card'));
    }

    /**
     * @throws Exception
     */
    public function testGetCourseMethod(): void
    {
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
        $crawler = self::getClient()->request('GET', '/courses/');
        self::getClient()->click($crawler->filter('.btn')->selectLink('Создать новый курс')->link());
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Добавить', [
            'course[code]' => 'course-1',
            'course[name]' => '12',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(2, $crawler->filter('li'));
        self::getClient()->submitForm('Добавить', [
            'course[code]' => 'course-4',
            'course[name]' => '123456',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(303);
        $crawler = self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains('Список курсов');
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        $this->assertCount(count($courses), $crawler->filter('.card'));
    }

    /**
     * @throws Exception
     */
    public function testEditCourseMethod(): void
    {
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        self::getClient()->request('GET', '/courses/' . $course->getId() . '/edit');
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Редактировать', [
            'course[code]' => 'course-2',
            'course[name]' => '12',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(422);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(2, $crawler->filter('li'));
        self::getClient()->submitForm('Редактировать', [
            'course[code]' => 'course-4',
            'course[name]' => '123456',
            'course[description]' => '12345678'
        ]);
        $this->assertResponseCode(303);
        self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains('123456 / StudyOn');
    }

    /**
     * @throws Exception
     */
    public function testDeleteCourseMethod(): void
    {
        $count = count(self::getEntityManager()->getRepository(Course::class)->findAll());
        $course = self::getEntityManager()->getRepository(Course::class)->findAll()[0];
        self::getClient()->request('GET', '/courses/' . $course->getId());
        $this->assertResponseCode(200);

        self::getClient()->submitForm('Удалить', []);
        $this->assertResponseCode(303);
        self::getClient()->followRedirect();
        $this->assertResponseOk();
        self::assertPageTitleContains('Список курсов / StudyOn');
        $newCount = count(self::getEntityManager()->getRepository(Course::class)->findAll());
        $this->assertEquals($newCount, $count - 1);
    }

    /**
     * @throws Exception
     */
    public function testPostMethods(): void
    {
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
