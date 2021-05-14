<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\MessageThread;
use App\Entity\User;
use App\Form\Cockpit\Post\ImageType;
use App\Form\Messages\FlagMessageThreadType;
use App\Repository\FlaggedMessageThreadRepository;
use App\Repository\MessageThreadRepository;
use App\Services\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/forum")
 */
class ForumController extends AbstractController
{
    /**
     * @Route("/list", methods={"GET"}, name="app_member_public_forum")
     */
    public function forumMessages(
        Request $request,
        MessageThreadRepository $messageThreadRepository,
        FlaggedMessageThreadRepository $flaggedThreadRepository,
        MessageService $messageService
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        $selectedThread = $messageThreadRepository->findOneBy(
            [
                'id' => $selectedThreadId = $request->query->getInt('thread', 0),
                'type' => MessageThread::TYPE_FORUM
            ]
        );

        $selectedThreadMessages = $selectedThread ?
            $messageService->getPublicForumMessages($selectedThread, $user !== null) : [];

        $pagination = $messageThreadRepository->getPaginatedPublicForumThreads(
            $request->query->getInt('page', 1),
            $query = $request->query->get('query')
        );

        $threads = $messageService->getPublicForumThreadsDataWrapper(
            $pagination->getItems(),
            $user !== null
        );

        if ($user && $user->isVerified() && $selectedThread) {
            $imageUploadForm = $this->createForm(ImageType::class, null, [
                'action' => $this->generateUrl('app_message_upload_image', ['id' => $selectedThread->getId()])
            ])->createView();

            if(!$flaggedThreadRepository->isThreadFlaggedAsInappropriateByUser($user, $selectedThread)) {
                $flagThreadForm = $this->createForm(FlagMessageThreadType::class, null, [
                    'action' => $this->generateUrl('app_thread_flag_inappropriate', ['id' => $selectedThread->getId()])
                ])->createView();
            }
        }

        return $this->render(
            'forum/thread_list.html.twig',
            [
                'query' => $query ?? null,
                'pagination' => $pagination,
                'threads' => $threads,
                'selectedThread' => $selectedThread,
                'selectedThreadMessages' => $selectedThreadMessages,
                'flagForm' => $flagThreadForm ?? null,
                'imageUploadForm' => $imageUploadForm ?? null
            ]
        );
    }
}
