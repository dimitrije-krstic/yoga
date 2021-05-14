<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Repository\TagRepository;
use App\Services\TagService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/tag")
 */
class TagController extends AbstractController
{
    /**
     * @Route("/list", methods={"GET"}, name="app_admin_tag_list")
     */
    public function listTags(
        Request $request,
        TagRepository $tagRepository
    ): Response {
        $paginatedTags = $tagRepository->getPaginatedTags(
            $request->query->getInt('page', 1),
            $request->query->get('query')
        );

        return $this->render(
            'admin/list_tags.html.twig',
            [
                'paginatedTags'  => $paginatedTags ,
            ]
        );
    }

    /**
     * Ajax
     * @Route("/edit", methods={"POST"}, name="app_admin_tag_edit")
     */
    public function editTagName(
        Request $request,
        TagRepository $tagRepository,
        TagService $tagService,
        EntityManagerInterface $entityManager
    ): Response {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['newSlug'], $data['tagId'])
            || !($tagToChange = $tagRepository->find($data['tagId'])))
        {
            return new Response('Required fields: tagId, newSlug', Response::HTTP_BAD_REQUEST);
        }

        $newSlug = mb_strtolower(str_replace(' ', '-', trim($data['newSlug'])));

        if ($existingTagWithSlug = $tagRepository->findOneBy(['slug' => $newSlug])) {
            $reassigned = $tagService->reassignTags($existingTagWithSlug, $tagToChange);
            $this->addFlash(
                $reassigned ? 'success' : 'error',
                $reassigned ? 'Tag name changed' : 'Error during Tag name change'
            );

            return new Response();
        }

        $tagToChange->setSlug($newSlug);
        $entityManager->flush();
        $this->addFlash('success', 'Tag name changed');

        return new Response();
    }

    /**
     * TODO change to DELETE
     * @Route("/delete/{id}", methods={"GET"}, name="app_admin_tag_delete")
     */
    public function deleteTag(
        Request $request,
        Tag $tag,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($tag);
        $entityManager->flush();

        return $this->redirectToRoute(
            'app_admin_tag_list',
            $request->query->all()
        );
    }
}
