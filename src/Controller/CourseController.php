<?php

namespace App\Controller;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Security\User;
use App\Service\BillingClient;
use App\Utils\ResponseParser;
use Exception;
use JsonException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/courses')]
class CourseController extends AbstractController
{
    private CourseRepository $courseRepository;
    private BillingClient $billingClient;
    private ResponseParser $responseParser;

    public function __construct(
        CourseRepository $courseRepository,
        BillingClient $billingClient,
        ResponseParser $responseParser
    ) {
        $this->courseRepository = $courseRepository;
        $this->billingClient = $billingClient;
        $this->responseParser = $responseParser;
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(): Response
    {
        $courses = $this->courseRepository->findAll();
        $transactions = [];

        $courseResponse = $this->billingClient->getCourses();
        $coursesArray = $this->responseParser->parseCourses($courseResponse, $courses);


        if ($this->getUser() !== null) {
            $user = $this->getUser();
            $transactionResponse = $this->billingClient->getTransactions(
                $user->getToken(),
                ['skip_expired' => true, 'type' => 'payment']
            );
            $transactions = $this->responseParser->parseTransactions($transactionResponse);
        }

        return $this->render('course/index.html.twig', [
            'courses' => $coursesArray,
            'transactions' => $transactions
        ]);
    }

    /**
     * @throws JsonException
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postFields = json_encode([
                'type' => $form->get('type')->getData(),
                'title' => $form->get('title')->getData(),
                'code' => $form->get('code')->getData(),
                'price' => $form->get('price')->getData(),
            ], JSON_THROW_ON_ERROR);

            try {
                $response = $this->billingClient->addCourse(
                    $this->getUser()->getToken(),
                    $postFields
                );
            } catch (BillingUnavailableException|JsonException $e) {
                throw new \RuntimeException('Произошла ошибка во добавления курса: ' . $e->getMessage());
            }
            if (isset($response['code'])) {
                if (isset($response['message'])) {
                    $form->addError(new FormError($response['message']));
                }
                if (isset($response['errors'])) {
                    foreach ($response['errors'] as $error) {
                        $form->addError(new FormError($error));
                    }
                }
            } elseif (isset($response['success'])) {
                $this->courseRepository->save($course, true);
                return $this->redirectToRoute(
                    'app_course_show',
                    ['id' => $course->getId()],
                    Response::HTTP_SEE_OTHER
                );
            }
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, Request $request): Response
    {

        $owned = false;
        $disabled = true;
        if ($this->getUser() !== null) {
            $user = $this->getUser();
            $courseBilling = $this->billingClient->getCourse($course->getCode());
            if (isset($courseBilling['type'])) {
                if ($courseBilling['type'] === 'free') {
                    $owned = true;
                } else {
                    $transactions = $this->billingClient->getTransactions(
                        $user->getToken(),
                        ['skip_expired' => true, 'course_code' => $course->getCode()]
                    );
                    if (isset($transactions[0])) {
                        $owned = true;
                    } else {
                        $currentUser = $this->billingClient->getBillingUser($user->getToken());
                        if ($currentUser['balance'] >= $courseBilling['price']) {
                            $disabled = false;
                        }
                    }
                }
            } else {
                $owned = true;
            }
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'owned' => $owned,
            'disabled' => $disabled

        ]);
    }

    /**
     * @throws \JsonException
     * @throws Exception
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course): Response
    {

        $courseBilling = $this->billingClient->getCourse($course->getCode());

        $form = $this->createForm(CourseType::class, $course);

        $form->get('type')->setData($courseBilling['type']);
        if (isset($courseBilling['price'])) {
            $form->get('price')->setData($courseBilling['price']);
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $postFields = json_encode([
                'type' => $form->get('type')->getData(),
                'title' => $form->get('title')->getData(),
                'code' => $form->get('code')->getData(),
                'price' => $form->get('price')->getData(),
            ], JSON_THROW_ON_ERROR);

            try {
                $response = $this->billingClient->editCourse(
                    $this->getUser()->getToken(),
                    $postFields,
                    $course->getCode()
                );
            } catch (BillingUnavailableException|JsonException $e) {
                throw new \RuntimeException('Произошла ошибка во добавления курса: ' . $e->getMessage());
            }
            if (isset($response['code'])) {
                if (isset($response['errors'])) {
                    foreach ($response['errors'] as $error) {
                        $form->addError(new FormError($error));
                    }
                }
                if (isset($response['message'])) {
                    $form->addError(new FormError($response['message']));
                }
            } else {
                $this->courseRepository->save($course, true);
                return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
            }
        }
        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $this->courseRepository->remove($course, true);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @throws BillingUnavailableException
     * @throws \JsonException
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/buy/{id}', name: 'app_course_buy', methods: ['POST'])]
    public function buy(Course $course, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        try {
            $response = $this->billingClient->buyCourse($user->getToken(), $course->getCode());
            if (isset($response['code'])) {
                $this->addFlash('error', $response['message']);
            } else {
                $this->addFlash('success', 'Курс успешно оплачен');
            }
        } catch (BillingUnavailableException| \Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
    }
}
