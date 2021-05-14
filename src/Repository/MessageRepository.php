<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Message;
use App\Entity\MessageThread;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function getAllThreadImages(MessageThread $thread): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.image')
            ->leftJoin('m.thread', 't')
            ->andWhere('t.id = :threadId')
            ->setParameter('threadId', $thread->getId())
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(array_column($result, 'image')));
    }

    public function getThreadMessages(MessageThread $thread, User $user): array
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m.image, m.content, m.createdAt, s.id as senderId, s.profileImage, s.name, s.slug')
            ->leftJoin('m.createdBy', 's')
            ->leftJoin('m.thread', 't')
            ->andWhere('t.id = :threadId')
            ->setParameter('threadId', $thread->getId())
            ->orderBy('m.createdAt', 'DESC')
            ->orderBy('m.id', 'DESC');

        if ($thread->getType() === MessageThread::TYPE_DIRECT_MESSAGES) {
            $qb->andWhere('t.createdBy = '.$user->getId().' OR t.receiver = '.$user->getId());
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getPublicForumThreadMessages(MessageThread $thread): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.image, m.content, m.createdAt, s.id as senderId, s.name, s.profileImage, s.slug, s.accountPubliclyVisible as visible')
            ->leftJoin('m.createdBy', 's')
            ->leftJoin('m.thread', 't')
            ->andWhere('t.id = :threadId')
            ->setParameter('threadId', $thread->getId())
            ->orderBy('m.createdAt', 'ASC')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function markSelectedMessagesAsRead(MessageThread $thread, User $user): void
    {
        if ($thread->getType() !== MessageThread::TYPE_DIRECT_MESSAGES
            && $thread->getCreatedBy()->getId() !== $user->getId()
        ) {
            return;
        }

        $this->createQueryBuilder('m')
            ->update()
            ->set('m.read', true)
            ->andWhere('m.thread = :threadId')
            ->setParameter('threadId', $thread->getId())
            ->andWhere('m.createdBy != '.$user->getId())
            ->getQuery()
            ->execute();
    }
}
