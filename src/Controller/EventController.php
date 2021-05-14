<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventComment;
use App\Entity\EventReview;
use App\Entity\EventTracker;
use App\Entity\User;
use App\Form\Cockpit\Event\EventCommentType;
use App\Form\Cockpit\Event\EventReviewType;
use App\Model\EventParticipantInfoWrapper;
use App\Repository\EventRepository;
use App\Repository\EventReviewRepository;
use App\Repository\EventTrackerRepository;
use App\Repository\UserRepository;
use App\Services\EventService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Removed TODO redo the concept
 *
 * @Route("/event")
 * @IsGranted("ROLE_ADMIN")
 */
class EventController extends AbstractController
{
    /**
     * @Route("/list", methods={"GET"}, name="app_event_list")
     * @Route("/member/{slug}/upcoming", methods={"GET"}, name="app_member_upcoming_events")
     */
    public function listAllEvents(
        Request $request,
        EventRepository $eventRepository,
        EventService $eventService,
        UserRepository $userRepository
    ): Response {
        $member = null;
        if ($slug = $request->get('slug')) {
            $member = $userRepository->getActiveUser($slug, true);
        }

        $pagination = $eventRepository->getPaginatedEvents(
            $request->query->getInt('page', 1),
            $request->query->getInt('category', 5),
            $request->query->get('from'),
            $request->query->get('to'),
            $member
        );

        if ($pagination->count() === 0) {
            $this->addFlash('error', 'Sorry, no events matched search criteria');

            return $this->render('event/list_page.html.twig');
        }

        $dataWrapper = $eventService->getEventTeaserDataWrapper($pagination->getItems(), $this->getUser());

        return $this->render(
            'event/list_page.html.twig',
            [
                'pagination' => $pagination,
                'eventDataWrapper' => $dataWrapper,
            ]
        );
    }

    /**
     * @Route("/bookmark/list", methods={"GET"}, name="app_event_bookmark_list")
     * @Route("/past-events-review/list", methods={"GET"}, name="app_event_review_list")
     * @IsGranted("ROLE_USER")
     */
    public function bookmarkedEventList(
        Request $request,
        EventRepository $eventRepository,
        EventService $eventService
    ): Response {
        $finishedEvents = $request->get('_route') === 'app_event_review_list';

        $pagination = $eventRepository->getPaginatedBookmarkedEvents(
            $this->getUser(),
            $request->query->getInt('page', 1),
            $request->query->getInt('category', 5),
            $request->query->get('from'),
            $request->query->get('to'),
            $finishedEvents
        );

        if ($pagination->count() === 0) {
            return $this->render('event/list_page.html.twig');
        }

        $dataWrapper = $eventService->getBookmarkedEventTeaserDataWrapperWithTracking(
            $pagination->getItems(),
            $this->getUser(),
            $finishedEvents
        );

        return $this->render(
            'event/list_page.html.twig',
            [
                'pagination' => $pagination,
                'eventDataWrapper' => $dataWrapper,
                'tracking' => $finishedEvents ? false : true
            ]
        );
    }

    /**
     * @Route("/{id}/view", methods={"GET", "POST"}, name="app_event_details")
     * @IsGranted("ROLE_USER")
     */
    public function viewEvent(
        Request $request,
        Event $event,
        EventRepository $eventRepository,
        EventService $eventService,
        EventReviewRepository $eventReviewRepository,
        EntityManagerInterface $entityManager,
        EventTrackerRepository $eventTrackerRepository
    ): Response {
        if ($event->getPublished() === null && !$this->isGranted('EVENT_AUTHOR', $event)) {
            $this->addFlash('error', 'No event found');
            return $this->redirectToRoute('app_event_list');
        }

        if ($event->getStart() < new \DateTime()) {
            return $this->redirectToRoute('app_event_review', ['id' => $event->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            if ($user->getId() === $event->getOrganizer()->getId()) {
                $event->setVisited();
                $entityManager->flush();
            } elseif ($tracker = $eventTrackerRepository->findOneBy(['event' => $event, 'user' => $user])) {
                $tracker->setVisited();
                $entityManager->flush();
            }

            $form = $this->createForm(EventCommentType::class, new EventComment($user, $event));
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $newComment = $form->getData();
                $entityManager->persist($newComment);
                $entityManager->flush();

                return $this->redirectToRoute('app_event_details', ['id' => $event->getId()]);
            }
        }

        $isParticipant = $eventRepository->isParticipant($user, $event);
        $moreUpcomingEvents = $eventService->getEventTeaserDataWrapper(
            $eventRepository->getMoreEventsFromSameAuthor($event),
            $user
        );

        $lastReviews = $eventReviewRepository->getLatestReviewsForOrganizer($event->getOrganizer());
        $commentDataWrapper = $eventService->getCommentDataWrappers($event);

        $participants = [];
         foreach($eventRepository->getEventParticipantImages($event) as $participant) {
             $participants[] = new EventParticipantInfoWrapper(
                 explode(' ', $participant['name'])[0],
                 $participant['slug'],
                 $participant['profileImage']
             );
         }

        return $this->render(
            'event/detail_page.html.twig',
            [
                'currentEvent' => $event,
                'participants' => $participants,
                'participantCount' => count($participants),
                'isParticipant' => $isParticipant,
                'eventDataWrapper' => $moreUpcomingEvents,
                'lastReviews' => $lastReviews,
                'commentDataWrapper' => $commentDataWrapper,
                'form' => isset($form) ? $form->createView() : null,
            ]
        );
    }

    /**
     * @Route("/{id}/review", methods={"GET", "POST"}, name="app_event_review")
     * @IsGranted("ROLE_USER")
     */
    public function reviewEvent(
        Event $event,
        Request $request,
        EventRepository $eventRepository,
        EventReviewRepository $eventReviewRepository,
        EntityManagerInterface $entityManager,
        EventService $eventService
    ): Response {
        if ($event->getStart() > new \DateTime()) {
            return $this->redirectToRoute('app_event_details', ['id' => $event->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();
        $isParticipant = $eventRepository->isParticipant($user, $event);
        $isGradedByParticipant = $eventReviewRepository->isGradedBy($user, $event);

        $allowGrading = $event->getCancelled() === null && $user->isVerified() && $isParticipant && !$isGradedByParticipant;

        if ($allowGrading) {
            $form = $this->createForm(EventReviewType::class, new EventReview($user, $event));
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $newReview = $form->getData();
                $entityManager->persist($newReview);
                $entityManager->flush();

                $this->addFlash('success', 'Your review has been added.');

                return $this->redirectToRoute('app_event_review_list');
            }
        }

        $reviews = $eventReviewRepository->getReviewsForEvent($event);

        $participantsCount = 0;
        if (!empty($result = $eventRepository->getParticipantNumberForEvents([$event->getId()]))) {
            $participantsCount = (int)$result[0]['participantsCount'];
        }

        return $this->render(
            'event/review_page.html.twig',
            [
                'currentEvent' => $event,
                'participantsCount' => $participantsCount,
                'reviews' => $reviews,
                'allowGrading' => $allowGrading,
                'form' => $allowGrading ? $form->createView() : null,
            ]
        );
    }

    /**
     * @Route("/member/{slug}/reviews", methods={"GET"}, name="app_event_member_reviews")
     * @IsGranted("ROLE_USER")
     */
    public function getMemberReviews(
        User $member,
        Request $request,
        EventReviewRepository $eventReviewRepository,
        EventRepository $eventRepository
    ): Response {
        $pagination = $eventReviewRepository->getPaginatedReviewsForOrganizer(
            $member,
            $request->query->getInt('page', 1)
        );

        $pastEvents = $eventRepository->countPastEventsForMember($member);

        return $this->render(
            'event/review_list_page.html.twig',
            [
                'pastEvents' => $pastEvents,
                'member' => $member,
                'pagination' => $pagination
            ]
        );
    }

    /**
     * Ajax
     * @Route("/bookmark/{id}", methods={"POST", "GET"}, name="app_event_bookmark")
     * @IsGranted("ROLE_USER")
     */
    public function bookmarkEvent(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        EventRepository $eventRepository,
        EventTrackerRepository $eventTrackerRepository
    ) {
        /** @var User $user */
        $user = $this->getUser();

        $action = null;
        if ($event->getPublished() !== null
            && $event->getStart() > new \DateTime()
            && $event->getOrganizer()->getId() !== $user->getId())
        {
            if ($eventRepository->isParticipant($user, $event)) {
                $event->removeParticipant($user);
                if ($tracker = $eventTrackerRepository->findOneBy(['event' => $event, 'user' => $user])) {
                    $entityManager->remove($tracker);
                }
                $action = 'remove';
            } elseif ($event->getCancelled() === null) {
                $event->addParticipant($user);
                $tracker = new EventTracker($user, $event);
                $entityManager->persist($tracker);
                $action = 'add';
            }

            $entityManager->flush();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse($action);
        }

        return $this->redirectToRoute('app_event_bookmark_list');
    }
}
