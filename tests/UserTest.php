<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Exception\BillingException;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class UserTest extends AbstractTest
{

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
    public function setUp(): void
    {
        parent::setUp();
    }

    public function setUpClient()
    {
        self::getClient()->disableReboot();

        self::getClient()->getContainer()->set(
            BillingClient::class,
            new BillingClientMock('')
        );

        return self::getClient();
    }

    public function testProfile(): void
    {
        $client = $this->setUpClient();
        $client->request('GET', '/profile');
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        self::authorizeUser($crawler, $client, $this);
        $crawler = $client->request('GET', '/profile');
        $this->assertResponseOk();
        self::assertSelectorExists('#email');
        self::assertSelectorTextContains('#email', 'user@gmail.com');
        self::assertSelectorExists('#role');
        self::assertSelectorTextContains('#role', 'Пользователь');
        $link = $crawler->selectLink('Выйти')->link();
        $client->click($link);
        $this->assertResponseRedirect();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        $crawler = $client->request('GET', '/profile');
        $this->assertResponseOk();
        self::assertSelectorExists('#email');
        self::assertSelectorTextContains('#email', 'admin@gmail.com');
        self::assertSelectorExists('#role');
        self::assertSelectorTextContains('#role', 'Администратор');
    }

    /**
     * @throws \Exception
     */
    public function testRoles()
    {
        $client = $this->setUpClient();
        $client->request('GET', '/courses/');
        $this->assertResponseOk();
        $courses = self::getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            $crawler = self::getClient()->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();
            $crawler = self::getClient()->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseRedirect();
            $crawler = self::getClient()->request('POST', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseRedirect();
            $crawler = self::getClient()->request('POST', '/courses/' . $course->getId());
            $this->assertResponseRedirect();
            $crawler = self::getClient()->request('GET', '/lessons/new/' . $course->getId());
            $this->assertResponseRedirect();
            $crawler = self::getClient()->request('POST', '/lessons/new/' . $course->getId());
            $this->assertResponseRedirect();
        }
        $lessons = self::getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseRedirect();
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseRedirect();
            $crawler = self::getClient()->request('POST', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseRedirect();
            $crawler = self::getClient()->request('POST', '/lessons/' . $lesson->getId());
            $this->assertResponseRedirect();
        }
        $crawler = $client->request('GET', '/login');
        self::authorizeUser($crawler, $client, $this);
        $client->request('GET', '/courses/');
        $this->assertResponseOk();
        foreach ($courses as $course) {
            $crawler = self::getClient()->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();
            $crawler = self::getClient()->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseCode(403);
            $crawler = self::getClient()->request('POST', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseCode(403);
            $crawler = self::getClient()->request('POST', '/courses/' . $course->getId());
            $this->assertResponseCode(403);
            $crawler = self::getClient()->request('GET', '/lessons/new/' . $course->getId());
            $this->assertResponseCode(403);
            $crawler = self::getClient()->request('POST', '/lessons/new/' . $course->getId());
            $this->assertResponseCode(403);
        }
        foreach ($lessons as $lesson) {
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseOk();
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseCode(403);
            $crawler = self::getClient()->request('POST', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseCode(403);
            $crawler = self::getClient()->request('POST', '/lessons/' . $lesson->getId());
            $this->assertResponseCode(403);
        }
        $crawler = $client->request('GET', '/login');
        $this->assertResponseRedirect();
        $crawler = $client->request('GET', '/registration');
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        $link = $crawler->selectLink('Выйти')->link();
        $client->click($link);
        $this->assertResponseRedirect();
        $crawler = $client->request('GET', '/login');
        self::authorizeAdmin($crawler, $client, $this);
        foreach ($lessons as $lesson) {
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseOk();
            $crawler = self::getClient()->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
            $crawler = self::getClient()->request('POST', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
            $crawler = self::getClient()->request('POST', '/lessons/' . $lesson->getId());
            $this->assertResponseRedirect();
        }
        $client->request('GET', '/courses/');
        $this->assertResponseOk();
        foreach ($courses as $course) {
            $crawler = self::getClient()->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();
            $crawler = self::getClient()->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();
            $crawler = self::getClient()->request('POST', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();
            $crawler = self::getClient()->request('GET', '/lessons/new/' . $course->getId());
            $this->assertResponseOk();
            $crawler = self::getClient()->request('POST', '/lessons/new/' . $course->getId());
            $this->assertResponseOk();
            $crawler = self::getClient()->request('POST', '/courses/' . $course->getId());
            $this->assertResponseRedirect();
        }
        $crawler = $client->request('GET', '/login');
        $this->assertResponseRedirect();
        $crawler = $client->request('GET', '/registration');
        $this->assertResponseRedirect();
    }

    public function testLogin(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        $link = $crawler->selectLink('Войти')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $expectedClass = BillingException::class;
        $expectedMessage = 'Ошибка авторизации. Проверьте правильность данных!';

        $form = $crawler->selectButton('Войти')->form(
            [
                'email' => 'user@qwe',
                'password' => 'password'
            ]
        );

        try {
            $client->submit($form);
        } catch (\Exception $e) {
            $this->assertSame($expectedClass, get_class($e));
            $this->assertSame($expectedMessage, $e->getMessage());
        }
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Войти')->form(
            [
                'email' => 'user@qwe.com',
                'password' => 'pd'
            ]
        );
        try {
            $client->submit($form);
        } catch (\Exception $e) {
            $this->assertSame($expectedClass, get_class($e));
            $this->assertSame($expectedMessage, $e->getMessage());
        }
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();
        self::authorizeUser($crawler, $client, $this);
        $this->assertResponseRedirect();
        $crawler = $client->followRedirect();
        self::assertPageTitleContains('Список курсов / StudyOn');
    }
    public function testBadPasswordRegistration(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/registration');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Зарегистрироваться')->form(
            [
                'user_registration_form[password][first]' => '12345678',
                'user_registration_form[password][second]' => '1231',
                'user_registration_form[agreeTerms]' => true,
                'user_registration_form[email]' => 'user@qwe.com',
            ]
        );
        $client->submit($form);

        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        $this->assertSame('Пароли должны совпадать.', $crawler->filter('li')->text());
    }

    public function testAgreeTermsRegistration(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/registration');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Зарегистрироваться')->form(
            [
                'user_registration_form[password][first]' => '12345678',
                'user_registration_form[password][second]' => '12345678',
                'user_registration_form[agreeTerms]' => false,
                'user_registration_form[email]' => 'user@qwe.com',
            ]
        );
        $client->submit($form);
        $this->assertResponseOk();
        self::assertPageTitleContains('Register!');
    }

    public function testBadEmailRegistration(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/registration');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Зарегистрироваться')->form(
            [
                'user_registration_form[password][first]' => '12345678',
                'user_registration_form[password][second]' => '12345678',
                'user_registration_form[agreeTerms]' => true,
                'user_registration_form[email]' => 'user@qwe',
            ]
        );
        $client->submit($form);

        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        $this->assertSame('Электронная почта неправильно заполнена.', $crawler->filter('li')->text());
    }

    public function testDuplicateRegistration(): void
    {
        $client = $this->setUpClient();
        $crawler = $client->request('GET', '/registration');
        $this->assertResponseOk();

        $form = $crawler->selectButton('Зарегистрироваться')->form(
            [
                'user_registration_form[password][first]' => '12345678',
                'user_registration_form[password][second]' => '12345678',
                'user_registration_form[agreeTerms]' => true,
                'user_registration_form[email]' => 'user@gmail.com',
            ]
        );
        $client->submit($form);
        $crawler = self::getClient()->getCrawler()->filter('form');
        $this->assertCount(1, $crawler->filter('li'));
        $this->assertSame(
            'Пользователь с такой электронной почтой уже существует!',
            $crawler->filter('li')->text()
        );
    }

//    public function testSuccessfulRegistration(): void
//    {
//        $client = $this->setUpClient();
//        $crawler = $client->request('GET', '/registration');
//        $this->assertResponseOk();
//
//        $form = $crawler->selectButton('Зарегистрироваться')->form(
//            [
//                'user_registration_form[password][first]' => '12345678',
//                'user_registration_form[password][second]' => '12345678',
//                'user_registration_form[agreeTerms]' => true,
//                'user_registration_form[email]' => 'user1231@gmail.com',
//            ]
//        );
//        $client->submit($form);
//        $this->assertResponseOk();
//        self::assertPageTitleContains('Список курсов / StudyOn');
//    }
}
