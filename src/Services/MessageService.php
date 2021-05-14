<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use App\Model\Cockpit\MessageThreadWrapper;
use App\Model\Cockpit\MessageWrapper;
use App\Repository\MessageRepository;
use App\Repository\MessageThreadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class MessageService
{
    private MessageRepository $messageRepository;
    private MessageThreadRepository $threadRepository;
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(
        MessageRepository $messageRepository,
        MessageThreadRepository $threadRepository,
        EntityManagerInterface $entityManager,
        Security $security
    ) {
        $this->messageRepository = $messageRepository;
        $this->threadRepository = $threadRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function createNewDirectMessageThread(User $from, User $to, string $subject, string $content): MessageThread
    {
        $message = new Message($from);
        $message->setContent($content);

        $messageThread = MessageThread::createDirectMessageThread($from, $to, $this->security->isGranted('ROLE_ADMIN'));
        $messageThread->setSubject($subject);
        $messageThread->addMessage($message);

        $this->entityManager->persist($messageThread);
        $this->entityManager->flush();

        return $messageThread;
    }

    public function sendAutomaticNotification(
        Request $request,
        string $link,
        string $messageSubject,
        string $messageContent
    ): void {
        $domain = $request->isSecure() ? 'https://' : 'http://';
        $host = $request->getHost();

        /** @var User $user */
        $user = $this->security->getUser();

        $message = new Message($user);
        $message->setContent($messageContent . ' ' . $domain . $host . $link);

        $thread = MessageThread::createNotificationThread($user, false);
        $thread->setSubject($messageSubject);
        $thread->addMessage($message);

        $this->entityManager->persist($thread);
        $this->entityManager->flush();
    }

    /**
     * @return MessageThreadWrapper[]
     */
    public function getThreadsDataWrapper(string $activeTab, array $threadsArray, User $user): array
    {
        $threadIds = [];
        foreach ($threadsArray as $thread) {
            $threadIds[] = (int) $thread['id'];
        }

        $unreadThreadIds = $this->threadRepository->getOnlyUnreadThreadsIds(
            $activeTab,
            $threadIds,
            $user
        );

        $threads = [];
        foreach ($threadsArray as $thread) {
            $threads[] = new MessageThreadWrapper(
                $id = (int) $thread['id'],
                $thread['updatedAt'],
                $thread['subject'],
                $thread['slug'],
                $thread['name'],
                $thread['profileImage'],
                !in_array($id, $unreadThreadIds, true),
                (int) $thread['messageCount']
            );
        }

        return $threads;
    }

    /**
     * @return MessageThreadWrapper[]
     */
    public function getPublicForumThreadsDataWrapper(array $threadsArray, bool $registeredMember): array
    {
        $threads = [];
        foreach ($threadsArray as $thread) {
            $threads[] = new MessageThreadWrapper(
                $id = (int) $thread['id'],
                $thread['updatedAt'],
                $thread['subject'],
                $registeredMember || !empty($thread['visible']) ? $thread['slug'] : '',
                $registeredMember || !empty($thread['visible']) ? $thread['name'] : explode(' ', $thread['name'])[0],
                $registeredMember || !empty($thread['visible']) ? $thread['profileImage'] : null,
                true,
                (int) $thread['messageCount']
            );
        }

        return $threads;
    }

    /**
     * @return MessageThreadWrapper[]
     */
    public function getNotificationThreadsDataWrapper(User $member, ?\DateTime $lastVisit, User $user): array
    {
        $threadsArray = $this->threadRepository->getMembersLastNotifications($member);

        $threadIds = [];
        $uniqueThreads = [];
        foreach ($threadsArray as $thread) {
            if (!in_array(($threadId = (int) $thread['id']), $threadIds, true)) {
                $threadIds[] = $threadId;
                $uniqueThreads[$threadId] = $thread;
                $uniqueThreads[$threadId]['allMessages'][] = $thread['content'];
            } else {
                $uniqueThreads[$threadId]['allMessages'][] = $thread['content'];
            }
        }

        $unreadThreads = $this->threadRepository->getUnreadUpdateThreadsIds(
            $threadIds,
            $user,
            $lastVisit
        );

        $threads = [];
        foreach ($uniqueThreads as $thread) {
            $threads[] = new MessageThreadWrapper(
                $id = (int) $thread['id'],
                $thread['updatedAt'],
                $thread['subject'],
                $thread['slug'],
                explode(' ', $thread['name'])[0],
                $thread['profileImage'],
                !in_array($id, $unreadThreads),
                (int) $thread['messageCount'],
                $thread['allMessages'] ?? []
            );
        }

        return $threads;
    }

    /**
     * Fetches all messages of the selected thread
     * and marks all of messages from sender as read
     *
     * @return MessageWrapper[]
     */
    public function readMessagesForThread(?MessageThread $thread, User $user): array
    {
        if ($thread === null) {
            return [];
        }

        $selectedMessages = $this->getSelectedThreadMessages($thread, $user);

        $this->messageRepository->markSelectedMessagesAsRead(
            $thread,
            $user
        );

        return $selectedMessages;
    }

    public function getPublicForumMessages(MessageThread $thread, bool $registeredMember): array
    {
        $result = $this->messageRepository->getPublicForumThreadMessages($thread);

        $selectedMessages = [];
        foreach ($result as $data) {
            $selectedMessages[] = new MessageWrapper(
                $data['content'],
                (int) $data['senderId'],
                $registeredMember || $data['visible'] ? $data['profileImage'] : null,
                $data['createdAt'],
                explode(' ', $data['name'])[0],
                $data['slug'],
                $data['image']
            );
        }

        return $selectedMessages;
    }

    /**
     * @return MessageWrapper[]
     */
    public function getSelectedThreadMessages(MessageThread $thread, User $user): array
    {
        $result = $this->messageRepository->getThreadMessages($thread, $user);

        $selectedMessages = [];
        foreach ($result as $data) {
            $selectedMessages[] = new MessageWrapper(
                $data['content'],
                (int) $data['senderId'],
                $data['profileImage'],
                $data['createdAt'],
                explode(' ', $data['name'])[0],
                $data['slug'],
                $data['image']
            );
        }

        return $selectedMessages;
    }
}
