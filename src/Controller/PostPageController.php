<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\FlaggedPost;
use App\Entity\Post;
use App\Entity\PostComment;
use App\Entity\User;
use App\Form\PublicPostPage\FlagPostType;
use App\Form\PublicPostPage\PostCommentType;
use App\Repository\FlaggedPostRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Services\PostService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/post")
 */
class PostPageController extends AbstractController
{
    private $postRepository;
    private $postService;

    public function __construct(
        PostRepository $postRepository,
        PostService $postService
    ) {
        $this->postRepository = $postRepository;
        $this->postService = $postService;
    }

    /**
     * @Route("/view/{slug}", methods={"GET", "POST"}, name="app_post_view_page")
     */
    public function getPostPage(
        Post $post,
        Request $request,
        EntityManagerInterface $entityManager,
        FlaggedPostRepository $flaggedPostRepository
    ): Response {
        if ($post->getPublishedAt() === null && !$this->isGranted('POST_AUTHOR', $post)) {
            $this->addFlash('error', 'No post found');
            $this->redirectToRoute('app_homepage');
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if ($user && $user->isVerified()) {
            //ADD COMMENT
            $form = $this->createForm(PostCommentType::class, new PostComment($post, $user));
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $newComment = $form->getData();
                $entityManager->persist($newComment);
                $entityManager->flush();

                return $this->redirectToRoute('app_post_view_page', ['slug' => $post->getSlug()]);
            }

            //FLAG POST
            $flagPostForm = $this->createForm(FlagPostType::class, null, [
                'action' => $this->generateUrl('app_post_flag_inappropriate', ['id' => $post->getId()])
            ]);
        }

        $moreAuthorPosts = $this->postService->getPostTeaserDataWrapper(
            $this->postRepository->getMorePostsFromSameAuthor($post),
            $user
        );
        $isPostLikedByUser = $this->postRepository->isPostLikedByUser($user, $post);
        $isPostFavoriteByUser = $this->postRepository->isPostFavoriteByUser($user, $post);
        $isFlaggedInappropriateByUser = $flaggedPostRepository->isPostFlaggedAsInappropriateByUser($user, $post);
        $commentDataWrapper = $user ? $this->postService->getCommentDataWrappers($post) : [];
        $numberOfComments = $this->postService->getNumberOfComments($post);

        return $this->render(
            'post/post_page.html.twig',
            [
                'post' => $post,
                'isPostLikedByUser' => $isPostLikedByUser,
                'isPostFavoriteByUser' => $isPostFavoriteByUser,
                'isFlaggedByUser' => $isFlaggedInappropriateByUser,
                'dataWrapper' => $moreAuthorPosts,
                'commentDataWrapper' => $commentDataWrapper,
                'commentsNo' => $numberOfComments,
                'form' => isset($form) ? $form->createView() : null,
                'flagForm' => isset($flagPostForm) ? $flagPostForm->createView() : null
            ]
        );
    }

    /**
     * @Route("/list", methods={"GET"}, name="app_public_post_list")
     */
    public function getPostCategoryPage(Request $request): Response
    {
        $posts = $this->postRepository->getPaginatedPostsForCategory(
            $request->query->get('category', ''),
            $request->query->getInt('page', 1),
            $request->query->get('query')
        );

        return $this->renderPostOverviewPageResponse($posts);
    }

    /**
     * @Route("/member/{slug}", methods={"GET"}, name="app_post_member_page")
     */
    public function getPostUserPage(
        string $slug,
        Request $request,
        UserRepository $userRepository
    ): Response {
        $posts = null;
        if ($member = $userRepository->findOneBy(['slug' => $slug])) {
            $posts = $this->postRepository->getPaginatedPostsForAuthor(
                $member,
                $request->query->getInt('page', 1),
                $request->query->get('query'),
                $request->query->get('category', ''),
            );
        }

        return $this->renderPostOverviewPageResponse($posts);
    }

    /**
     * @Route("/my-favorites", methods={"GET"}, name="app_post_favorites_page")
     * @IsGranted("ROLE_USER")
     */
    public function getPostFavoritesPage(
        Request $request,
        PostRepository $postRepository
    ): Response {
        $posts = $postRepository->getPaginatedFavoritePosts(
            $this->getUser(),
            $request->query->getInt('page', 1),
            $request->query->get('category', ''),
            $request->query->get('query')
        );

        return $this->renderPostOverviewPageResponse($posts, true);
    }

    /**
     * Ajax
     * @Route("/like/{id}", methods={"POST"}, name="app_post_like")
     * @IsGranted("ROLE_USER")
     */
    public function likePost(
        Post $post,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $user->likePost($post);
        $entityManager->flush();

        return new JsonResponse(
            $post->getLikedBy()->count()
        );
    }

    /**
     * Ajax
     * @Route("/favorite/{id}", methods={"POST"}, name="app_post_favorites")
     * @IsGranted("ROLE_USER")
     */
    public function addPostToFavorites(
        Post $post,
        EntityManagerInterface $entityManager,
        PostRepository $postRepository
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($postRepository->isPostFavoriteByUser($user, $post)) {
            $user->removePostFromFavorites($post);
        } else {
            $user->addPostToFavorites($post);
        }

        $entityManager->flush();

        return new JsonResponse();
    }

    /**
     * @Route("/flag-inappropriate/{id}", methods={"POST"}, name="app_post_flag_inappropriate")
     * @IsGranted("ROLE_USER")
     */
    public function flagPostInappropriate(
        Request $request,
        Post $post,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(
            FlagPostType::class,
            $report = new FlaggedPost($post, $this->getUser())
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report = $form->getData();
            $entityManager->persist($report);
            $entityManager->flush();

            $this->addFlash('success', 'Thank you. We have received your Report.');
        }

        return $this->redirectToRoute(
            'app_post_view_page',
            [
                'slug' => $post->getSlug()
            ]
        );
    }

    private function renderPostOverviewPageResponse(?PaginationInterface $pagination, bool $isFavorite = false): Response
    {
        if ($pagination === null || $pagination->count() === 0) {
            $this->addFlash('error', 'Sorry, no posts matched search criteria');

            return $this->render('post/post_page_list.html.twig');
        }

        $dataWrapper = $this->postService->getPostTeaserDataWrapper($pagination->getItems(), $this->getUser());

        return $this->render(
            'post/post_page_list.html.twig',
            [
                'pagination' => $pagination,
                'dataWrapper' => $dataWrapper,
                'isFavorite' => $isFavorite
            ]
        );
    }

}
