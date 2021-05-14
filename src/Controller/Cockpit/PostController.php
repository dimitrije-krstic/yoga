<?php
declare(strict_types=1);

namespace App\Controller\Cockpit;

use App\Entity\Post;
use App\Entity\User;
use App\Form\Cockpit\Post\ImageType;
use App\Form\Cockpit\Post\PostType;
use App\Form\Cockpit\Post\VideoPostType;
use App\Repository\PostRepository;
use App\Services\MessageService;
use App\Services\PostService;
use App\Services\UploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/cockpit/post")
 */
class PostController extends AbstractController
{
    /**
     * @var UploadHelper
     */
    private $uploadHelper;

    public function __construct(UploadHelper $uploadHelper)
    {
        $this->uploadHelper = $uploadHelper;
    }

    /**
     * @Route("/list", methods={"GET"}, name="app_user_post_list")
     */
    public function getAllPosts(
        Request $request,
        PostRepository $postRepository,
        PostService $postService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $pagination = $postRepository->getOwnPostsPaginated(
            $user,
            $request->query->getInt('page', 1),
            $request->query->get('category', ''),
            $request->query->get('query')
        );

        $dataWrapper = $postService->getPostTeaserDataWrapper($pagination->getItems(), $user);

        return $this->render(
            'post/post_page_list.html.twig',
            [
                'dataWrapper' => $dataWrapper,
                'pagination' => $pagination,
                'searchPlaceholder' => 'Search by title',
                'myPosts' => true
            ]
        );
    }

    /**
     * @Route("/create", methods={"GET", "POST"}, name="app_user_create_post")
     * @Route("/create-vlog", methods={"GET", "POST"}, name="app_user_create_video_post")
     */
    public function createPost(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->isVerified()) {
            $this->addFlash('error','Please, verify your email to be able to contribute.');
            return $this->redirectToRoute('app_user_account');
        }

        $isVideoPost = $request->get('_route') === 'app_user_create_video_post';

        $form = $this->createForm($isVideoPost ? VideoPostType::class : PostType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $this->uploadPostImage($form);

            if ($this->isGranted('ROLE_ADMIN')) {
                $post->setWebPost(true);
            }

            $entityManager->persist($post);
            $entityManager->flush();
            $this->addFlash('success','Congrats! You created a new '. ($isVideoPost ? 'vlog.' : 'post.'));

            return $this->redirectToRoute(
                $isVideoPost ? 'app_user_edit_video_post' : 'app_user_edit_post',
                ['id' => $post->getId()]
            );
        }

        return $this->render(
            'post/cockpit/new.html.twig',
            [
                'form' => $form->createView(),
                'isVideoPost' => $isVideoPost,
                'post' => null
            ]
        );
    }

    /**
     * @Route("/edit/{id}", methods={"GET", "POST"}, name="app_user_edit_post")
     * @Route("/edit-vlog/{id}", methods={"GET", "POST"}, name="app_user_edit_video_post")
     * @IsGranted("POST_AUTHOR", subject="post")
     */
    public function editPost(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager
    ): Response {
        $isVideoPost = $request->get('_route') === 'app_user_edit_video_post';

        if (!$isVideoPost && $post->getYoutubeVideoId()) {
            return $this->redirectToRoute('app_user_edit_video_post', ['id' => $post->getId()]);
        }

        if ($isVideoPost && $post->getYoutubeVideoId() === null) {
            return $this->redirectToRoute('app_user_edit_post', ['id' => $post->getId()]);
        }

        $form = $this->createForm(
            $isVideoPost ? VideoPostType::class : PostType::class,
            $post
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success','Changes have been saved.');

            return $this->redirectToRoute(
                $isVideoPost ? 'app_user_edit_video_post' : 'app_user_edit_post',
                ['id' => $post->getId()]
            );
        }

        $imageUploadForm = null;
        if (!$isVideoPost) {
            $imageUploadForm = $this->createForm(ImageType::class, null, [
                'action' => $this->generateUrl('app_user_add_post_image', ['id' => $post->getId()])
            ]);
        }

        return $this->render(
            'post/cockpit/edit.html.twig',
            [
                'form' => $form->createView(),
                'isVideoPost' => $isVideoPost,
                'post' => $post,
                'imageUploadForm' => $isVideoPost ? null : $imageUploadForm->createView(),
            ]
        );
    }

    /**
     * //TODO change to POST
     * @Route("/publish/{id}", methods={"GET"}, name="app_user_publish_post")
     * @IsGranted("POST_AUTHOR", subject="post")
     */
    public function publishPost(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        MessageService $messageService
    ): Response {
        if ($post->getPublishedAt() === null) {
            $post->setPublishedAt(new \DateTime());
            $entityManager->flush();

            // notify your network about new post
            $messageService->sendAutomaticNotification(
                $request,
                $this->generateUrl('app_post_view_page', ['slug' => $post->getSlug()]),
                'New post',
                'Check my new post: "'.$post->getTitle().'"'
            );

            $this->addFlash('success','Congrats. Your post has been published.');
        }

        $referer = $request->headers->get('referer');

        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_user_post_list');
    }

    /**
     * //TODO change it to DELETE
     * @Route("/delete/{id}", methods={"GET"}, name="app_user_delete_post")
     *
     * @Security("is_granted('ROLE_MASTER') or is_granted('POST_AUTHOR', post)")
     */
    public function deletePost(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($post);
        $entityManager->flush();
        $this->addFlash('success','Your post has been deleted.');

        return $this->redirectToRoute('app_user_post_list', $request->query->all());
    }

    /**
     * //TODO change it to DELETE
     * @Route("/image/delete/{id}/{fileName}", methods={"GET"}, name="app_user_delete_post_image")
     * @IsGranted("POST_AUTHOR", subject="post")
     */
    public function deletePostImage(
        Post $post,
        string $fileName,
        EntityManagerInterface  $entityManager,
        UploadHelper $uploadHelper
    ): Response {
        $post->removeImage($fileName);
        $uploadHelper->deletePostImage($fileName);
        $entityManager->flush();
        $this->addFlash('success','Your image has been deleted.');

        return $this->redirectToRoute(
            'app_user_edit_post',
            [
                'id' => $post->getId()
            ]
        );
    }

    /**
     * @Route("/image/add/{id}", methods={"POST"}, name="app_user_add_post_image")
     * @IsGranted("POST_AUTHOR", subject="post")
     */
    public function addPostImage(
        Post $post,
        Request $request,
        EntityManagerInterface  $entityManager
    ): Response {
        $form = $this->createForm(ImageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->uploadPostImage($form, $post);
            $entityManager->flush();
        }

        $this->addFlash('success', 'post added');

        return $this->redirectToRoute(
            'app_user_edit_post',
            [
                'id' => $post->getId()
            ]
        );
    }

    private function uploadPostImage(FormInterface $form, ?Post $post = null): Post
    {
        /** @var Post $post */
        $post = $post ?? $form->getData();

        if ($form->has('imageFile')
            && ($file = $form->get('imageFile')->getData())
            && count($post->getImages() ?? []) < Post::MAX_NUMBER_OF_IMAGES
        ) {
            $fileName = $this->uploadHelper->uploadPostImage($file);
            $post->addImage($fileName);
        }

        return $post;
    }
}
