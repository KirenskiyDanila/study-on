<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/lessons')]
class LessonController extends AbstractController
{
    #[Route('/new/{id}', name: 'app_lesson_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        int $id,
        LessonRepository $lessonRepository,
        CourseRepository $courseRepository
    ): Response {
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);
        $lesson->setCourse($courseRepository->find(['id' => $id]));

        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository->checkSerialNumber($lesson);
            $lessonRepository->save($lesson, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $lesson->getCourse()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lesson_show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_lesson_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson, LessonRepository $lessonRepository): Response
    {
        $course = $lesson->getCourse();
        $oldSerialNumber = $lesson->getSerialNumber();
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository->moveSerialNumbers($lesson, $oldSerialNumber);
            $lessonRepository->checkSerialNumber($lesson);
            $lesson->setCourse($course);
            $lessonRepository->save($lesson, true);

            return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_lesson_delete', methods: ['POST'])]
    public function delete(Request $request, Lesson $lesson, LessonRepository $lessonRepository): Response
    {
        $id = $lesson->getCourse()->getId();
        if ($this->isCsrfTokenValid('delete'.$lesson->getId(), $request->request->get('_token'))) {
            $lessonRepository->moveSerialNumbers($lesson, $lesson->getSerialNumber());
            $lessonRepository->remove($lesson, true);
        }

        return $this->redirectToRoute('app_course_show', ['id' => $id], Response::HTTP_SEE_OTHER);
    }
}
