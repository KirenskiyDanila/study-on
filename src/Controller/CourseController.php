<?php

namespace App\Controller;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Security\User;
use App\Service\BillingClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/courses')]
class CourseController extends AbstractController
{
    private CourseRepository $courseRepository;
    private BillingClient $billingClient;

    public function __construct(CourseRepository $courseRepository, BillingClient $billingClient)
    {
        $this->courseRepository = $courseRepository;
        $this->billingClient = $billingClient;
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
        if ($this->getUser() !== null) {
            $user = $this->getUser();
            $response = $this->billingClient->getTransactions(
                $user->getToken(),
                ['skip_expired' => true, 'type' => 'payment']
            );
            foreach ($response as $item) {
                if (isset($item['expires_at'])) {
                    $transactions[$item['course_code']]['type'] = 'rent';
                    $transactions[$item['course_code']]['expires_at'] = $item['expires_at'];
                } else {
                    $transactions[$item['course_code']]['type'] = 'buy';
                }
            }
        }

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
            'transactions' => $transactions

        ]);
    }
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {

        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->courseRepository->save($course, true);

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course, Request $request): Response
    {

        $owned = false;
        if ($this->getUser() !== null) {
            $user = $this->getUser();
            $response = $this->billingClient->getTransactions(
                $user->getToken(),
                ['skip_expired' => true, 'course_code' => $course->getCode()]
            );
            if (isset($response[0])) {
                $owned = true;
            }
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'owned' => $owned

        ]);
    }
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course): Response
    {

        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->courseRepository->save($course, true);

            return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
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
            $this->billingClient->buyCourse($user->getToken(), $course->getCode());
            $this->addFlash('success', 'Курс успешно оплачен');
        } catch (BillingUnavailableException| \Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_course_show', ['id' => $course->getId()], Response::HTTP_SEE_OTHER);
    }
}
