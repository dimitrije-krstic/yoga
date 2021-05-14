<?php
declare(strict_types=1);

namespace App\Controller\Cockpit;

use App\Entity\FlaggedMessageThread;
use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use App\Form\Cockpit\Post\ImageType;
use App\Form\Messages\FlagMessageThreadType;
use App\Repository\MessageRepository;
use App\Repository\MessageThreadRepository;
use App\Services\MessageService;
use App\Services\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cockpit/message")
 */
class MessageController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/{activeTab}",
     *     methods={"GET"},
     *     name="app_message_center",
     *     requirements={"activeTab"="inbox|sent|spam|admin|forum"}
     *)
     */
    public function messageCenter(
        string $activeTab,
        Request $request,
        MessageThreadRepository $messageThreadRepository,
        MessageService $messageService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $selectedThread = $messageThreadRepository->find(
            $selectedThreadId = $request->query->getInt('thread', 0)
        );

        $selectedThreadMessages = $messageService->readMessagesForThread(
            $selectedThread,
            $user
        );

        $pagination = $messageThreadRepository->getPaginatedThreads(
            $activeTab,
            $user,
            $request->query->getInt('page', 1),
            $query = $request->query->get('query')
        );

        $threads = $messageService->getThreadsDataWrapper(
            $activeTab,
            $pagination->getItems(),
            $user
        );

        if ($activeTab === 'forum' && $selectedThread) {
            $imageUploadForm = $this->createForm(ImageType::class, null, [
                'action' => $this->generateUrl('app_message_upload_image', ['id' => $selectedThread->getId()])
            ])->createView();
        }

        return $this->render(
            'message/thread_list.html.twig',
            [
                'activeTab' => $activeTab,
                'query' => $query ?: null,
                'pagination' => $pagination,
                'threads' => $threads,
                'selectedThread' => $selectedThread,
                'selectedThreadMessages' => $selectedThreadMessages,
                'unreadInbox' => $messageThreadRepository->countUnreadReceivedThreads('inbox', $user),
                'unreadAdmin' => $messageThreadRepository->countUnreadReceivedThreads('admin', $user),
                'unreadSent' => $messageThreadRepository->countUnreadSentThreads($user),
                'unreadForum' => $messageThreadRepository->countOwnUnreadForumThreads('forum',$user),
                'imageUploadForm' => $imageUploadForm ?? null
            ]
        );
    }

    /**
     * Ajax
     * @Route("/contact/{slug}", methods={"POST"}, name="app_member_message_contact")
     */
    public function contactMemberDirectly(
        User $member,
        Request $request
    ): JsonResponse{
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isVerified()) {
            return new JsonResponse('Please, verify your email to be able to contribute.', 400);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['subject'], $data['message'])) {
            return new JsonResponse('Subject and Message cannot be empty.', 400);
        }

        $this->createThread($data['subject'], $data['message'], $member);

        return new JsonResponse('Message has been sent', 200);
    }

    /**
     * Ajax & controller
     *
     * @Route("/notification-form", methods={"GET", "POST"}, name="app_message_notification_form")
     * @Route("/forum-form", methods={"GET", "POST"}, name="app_message_forum_form")
     */
    public function createNonDirectMessage(Request $request, MessageService $messageService)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isVerified()) {
            return new Response('Please, verify your email to be able to contribute');
        }

        $isNotification = $request->get('_route') === 'app_message_notification_form';

        if ($request->getMethod() === 'POST') {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['subject'], $data['message'])) {
                return new JsonResponse(
                    'Subject and Message cannot be empty',
                    400
                );
            }

            $thread = $this->createThread($data['subject'], $data['message'], null, $isNotification);

            // notify your network about new forum creation
            if (!$isNotification) {
                $messageService->sendAutomaticNotification(
                    $request,
                    $this->generateUrl('app_member_public_forum', ['thread' => $thread->getId()]),
                    'New forum',
                    'Check my new discussion topic: "'.$data['subject'].'".'
                );
            }

            return new JsonResponse(
                $isNotification ? 'Notification has been sent.' : 'Your forum topic has been added.',
                200
            );
        }

        return $this->render(
            'message/_ajax_non_direct_messages.html.twig',
            [
                'isNotification' => $isNotification
            ]
        );
    }

    /**
     * Ajax
     * @Route("/reply/{id}", methods={"POST"}, name="app_message_reply")
     */
    public function reply(
        MessageThread $thread,
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isVerified()) {
            $this->addFlash('error','Please, verify your email to be able to contribute.');
            return new JsonResponse();
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['message'])) {
            $this->addFlash('error','Message cannot be empty');
            return new JsonResponse();
        }

        $message = new Message($user);
        $message->setContent($data['message']);
        $thread->addMessage($message);
        $thread->setUpdatedAt(new \DateTime());

        if (($type = $thread->getType()) === MessageThread::TYPE_FORUM
            || $type === MessageThread::TYPE_NOTIFICATION
            || $thread->getCreatedBy()->getId() === ($userId = $user->getId())
            || $thread->getReceiver()->getId() === $userId
        ) {
            $this->entityManager->flush();
        }

        return new JsonResponse();
    }

    /**
     * TODO make it ajax call
     * @Route("/upload-image/{id}", methods={"POST"}, name="app_message_upload_image")
     */
    public function uploadImage(
        MessageThread $thread,
        Request $request,
        EntityManagerInterface $entityManager,
        UploadHelper $uploadHelper
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isVerified() || $thread->getType() !== MessageThread::TYPE_FORUM) {
            $this->addFlash('error','Please, verify your email to be able to contribute.');
            return $this->redirectToRoute('app_member_public_forum');
        }

        $form = $this->createForm(ImageType::class);
        $form->handleRequest($request);

        $fileName = '';
        if ($form->isSubmitted() && $form->isValid() && ($file = $form->get('imageFile')->getData())) {
            $fileName = $uploadHelper->uploadForumImage($file);

            $message = new Message($user);
            $message->setImage($fileName);
            $thread->addMessage($message);
            $thread->setUpdatedAt(new \DateTime());

            $entityManager->flush();
        }

        return $this->redirectToRoute('app_member_public_forum', ['thread' => $thread->getId()]);
    }

    /**
     * Ajax
     * @Route("/spam/{id}", methods={"POST"}, name="app_message_thread_spam")
     */
    public function spam(
        MessageThread $thread
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$thread->isAdmin()
            && $thread->getType() === MessageThread::TYPE_DIRECT_MESSAGES
            && $thread->getReceiver()->getId() === $user->getId()
        ) {
            $thread->isSpam() ? $thread->removeFromSpam() : $thread->markAsSpam();
            $this->entityManager->flush();
        }

        return new JsonResponse();
    }

    /**
     * TODO change to DELETE
     * @Route("/delete/{activeTab}/{id}", methods={"GET"}, name="app_message_thread_delete")
     * @Security("is_granted('ROLE_ADMIN') or is_granted('THREAD_AUTHOR', thread)")
     */
    public function deleteThread(
        string $activeTab,
        MessageThread $thread,
        MessageRepository $messageRepository,
        UploadHelper $uploadHelper
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')
            || $activeTab !== 'forum'
            || ($activeTab === 'forum' && $thread->getMessages()->count() < 6)
        ) {
            $images = $messageRepository->getAllThreadImages($thread);
            foreach ($images as $image) {
                $uploadHelper->deleteForumImage($image);
            }

            $this->entityManager->remove($thread);
            $this->entityManager->flush();
            $this->addFlash('success','Message thread deleted.');
        }

        return $this->redirectToRoute('app_message_center', ['activeTab' => $activeTab]);
    }

    /**
     * @Route("/delete-notification/{id}", methods={"POST"}, name="app_delete_notification")
     * @Security("is_granted('THREAD_AUTHOR', thread)")
     */
    public function deleteNotificationThread(
        MessageThread $thread
    ): JsonResponse {
        $this->entityManager->remove($thread);
        $this->entityManager->flush();

        return new JsonResponse('Notification deleted.');
    }

    /**
     * @Route("/flag-inappropriate/{id}", methods={"POST"}, name="app_thread_flag_inappropriate")
     */
    public function flagThreadInappropriate(
        Request $request,
        MessageThread $thread,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isVerified()) {
            return $this->redirectToRoute('app_user_account');
        }

        $form = $this->createForm(
            FlagMessageThreadType::class,
            $report = new FlaggedMessageThread($thread, $user)
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($report);
            $entityManager->flush();

            $this->addFlash('success', 'Thank you. We have received your Report.');
        }

        return $this->redirectToRoute('app_member_public_forum');
    }


    /**
     * Ajax
     * @Route("/count-all-unread", methods={"GET"}, name="app_member_message_count_all_unread")
     */
    public function countAllUnreadMessages(MessageThreadRepository $messageThreadRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $unreadInbox = $messageThreadRepository->countUnreadReceivedThreads('inbox', $user);
        $unreadAdmin = $messageThreadRepository->countUnreadReceivedThreads('admin', $user);
        $unreadSent = $messageThreadRepository->countUnreadSentThreads($user);
        $all = $unreadInbox + $unreadAdmin + $unreadSent;

        return new JsonResponse($all);
    }

    /**
     * Ajax
     * @Route("/forum/count-unread", methods={"GET"}, name="app_member_message_forum_count_unread")
     */
    public function countUnreadForumTopics(MessageThreadRepository $messageThreadRepository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $count = $messageThreadRepository->countOwnUnreadForumThreads('forum', $user);

        return new JsonResponse($count);
    }

    private function createThread(
        string $subject,
        string $messageContent,
        ?User $receiver,
        bool $isNotification = false
    ): MessageThread {
        /** @var User $user */
        $user = $this->getUser();

        $message = new Message($user);
        $message->setContent($messageContent);

        if ($receiver) {
            $thread = MessageThread::createDirectMessageThread($user, $receiver, $this->isGranted('ROLE_ADMIN'));
        } elseif ($isNotification) {
            $thread = MessageThread::createNotificationThread($user, $this->isGranted('ROLE_ADMIN'));
        } else {
            $thread = MessageThread::createForumThread($user);
        }

        $thread->setSubject($subject);
        $thread->addMessage($message);

        $this->entityManager->persist($thread);
        $this->entityManager->flush();

        return $thread;
    }
}
