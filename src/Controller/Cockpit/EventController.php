<?php
declare(strict_types=1);

namespace App\Controller\Cockpit;

use App\Entity\Event;
use App\Entity\EventComment;
use App\Entity\User;
use App\Form\Cockpit\Event\EventType;
use App\Repository\EventRepository;
use App\Services\EventService;
use App\Services\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Removed TODO redo the concept
 *
 * @Route("/cockpit/event")
 * @IsGranted("ROLE_ADMIN")
 */
class EventController extends AbstractController
{
    /**
     * @Route("/list", methods={"GET"}, name="app_cockpit_event_list")
     */
    public function listMyEvents(
        Request $request,
        EventRepository $eventRepository,
        EventService $eventService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $pagination = $eventRepository->getPaginatedUserEvents(
            $user,
            $request->query->getInt('page', 1),
            $request->query->getInt('category', 5),
            $request->query->get('from'),
            $request->query->get('to'),
        );

        if ($pagination->count() === 0) {
            return $this->render('event/list_page.html.twig');
        }

        $dataWrapper = $eventService->getUserEventTeaserDataWrapperWithTracking(
            $pagination->getItems(),
            $user
        );

        return $this->render(
            'event/list_page.html.twig',
            [
                'pagination' => $pagination,
                'eventDataWrapper' => $dataWrapper,
                'myEvents' => true,
                'tracking' => true
            ]
        );
    }

    /**
     * @Route("/create", methods={"GET", "POST"}, name="app_cockpit_event_create")
     */
    public function createEvent(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->getUser()->isVerified()) {
            $this->addFlash('error','Please, verify your email to be able to contribute.');
            return $this->redirectToRoute('app_user_account');
        }

        $form = $this->createForm(EventType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Event $event */
            $event = $form->getData();
            $entityManager->persist($event);
            $entityManager->flush();

            if ($form->has('repeat') && ($repeat = $form->get('repeat')->getData()) > 1) {
                for ($i=1; $i<$repeat; $i++) {
                    $repeatedEvent = clone $event;
                    $start = new \DateTime($event->getStart()->format('Y-m-d H:i:s'));
                    $start->modify('+'.($i*7).' days');
                    $repeatedEvent->setStart($start);
                    $entityManager->persist($repeatedEvent);
                }
                $entityManager->flush();
            }

            $this->addFlash('success','Congrats! You created a new Event');

            return $this->redirectToRoute('app_cockpit_event_edit', ['id' => $event->getId()]);
        }

        return $this->render(
            'event/cockpit/edit.html.twig',
            [
                'form' => $form->createView(),
                'event' => null
            ]
        );
    }

    /**
     * @Route("/edit/{id}", methods={"GET", "POST"}, name="app_cockpit_event_edit")
     * @IsGranted("EVENT_AUTHOR", subject="event")
     */
    public function editEvent(
        Request $request,
        EntityManagerInterface $entityManager,
        Event $event
    ): Response {
        if ($event->getStart() < new \DateTime() || $event->getCancelled() !== null) {
            return $this->redirectToRoute('app_cockpit_event_list');
        }

        $linkBefore = $event->getLink();
        $linkPassBefore = $event->getLinkPassword();

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($event->getCancelled() === null && $form->isSubmitted() && $form->isValid()) {

            if ($linkBefore !== $form->get('link')->getData() || $linkPassBefore !== $form->get('linkPassword')->getData()) {
                $comment = new EventComment($this->getUser(), $event);
                $comment->setContent('I have updated access credentials.');
                $entityManager->persist($comment);
            }

            $entityManager->flush();

            $this->addFlash('success','Changes have been saved.');

            return $this->redirectToRoute('app_cockpit_event_edit', ['id' => $event->getId()]);
        }

        return $this->render(
            'event/cockpit/edit.html.twig',
            [
                'form' => $form->createView(),
                'event' => $event
            ]
        );
    }

    /**
     * TODO change to POST
     * @Route("/publish/{id}", methods={"GET"}, name="app_cockpit_event_publish")
     * @Route("/cancel/{id}", methods={"GET"}, name="app_cockpit_event_cancel")
     *
     * @IsGranted("EVENT_AUTHOR", subject="event")
     */
    public function publishEvent(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        MessageService $messageService
    ): Response {
        if ($event->getStart() < new \DateTime()) {
            return $this->redirectToRoute('app_cockpit_event_list');
        }

        $isPublish = $request->get('_route') === 'app_cockpit_event_publish';
        $isCancel = $request->get('_route') === 'app_cockpit_event_cancel';

        if ($isPublish && $event->getPublished() === null) {
            $event->publishEvent();
            $entityManager->flush();

            $messageService->sendAutomaticNotification(
                $request,
                $this->generateUrl('app_event_details', ['id' => $event->getId()]),
                'New event',
                'Check my new event: "'.$event->getTitle().'".'
            );
        }

        if ($isCancel && $event->getPublished() && $event->getCancelled() === null) {
            $event->cancelEvent();
            $entityManager->flush();
        }

        $flashMessage = $isPublish ? 'Congrats. Your event has been published.' : 'Your event has been canceled.';
        $this->addFlash('success', $flashMessage);

        $referer = $request->headers->get('referer');

        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_cockpit_event_list');
    }

    /**
     * //TODO change it to DELETE
     * @Route("/delete/{id}", methods={"GET"}, name="app_cockpit_event_delete")
     * @IsGranted("EVENT_AUTHOR", subject="event")
     */
    public function deleteEvent(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository
    ): Response {
        if ($event->getPublished() === null ||
            ($event->getStart() < new \DateTime() &&
                ($event->getCancelled() !== null ||
                    empty((int)$eventRepository->getParticipantNumberForEvents([$event->getId()])[0]['participantsCount'])
                )
            )
        ) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success','Your event has been deleted.');
        }

        return $this->redirectToRoute('app_cockpit_event_list', $request->query->all());
    }

    /**
     * Ajax
     * @Route("/count/updates", methods={"GET"}, name="app_cockpit_count_event_updates")
     */
    public function countEventUpdates(EventRepository $eventRepository)
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse(
            $eventRepository->countUpdatesForUserEvents($user)
        );
    }
}
