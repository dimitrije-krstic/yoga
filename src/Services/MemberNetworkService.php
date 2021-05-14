<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\User;
use App\Model\Cockpit\MemberDataWrapper;
use App\Repository\NotificationTrackerRepository;

class MemberNetworkService
{
    private $notificationTrackerRepository;

    public function __construct(
        NotificationTrackerRepository $notificationTrackerRepository
    ) {
        $this->notificationTrackerRepository = $notificationTrackerRepository;
    }

    /**
     * @return MemberDataWrapper[]
     */
    public function getMembersDataWrapper(array $membersData, User $user): array
    {
        $memberIds = [];
        foreach ($membersData as $member) {
            $memberIds[] = (int) $member['id'];
        }

        $idsOfMembersWithUpdates = $this->notificationTrackerRepository->getMemberIdsWithUnreadNotifications(
            $memberIds,
            $user
        );

        $members = [];
        foreach ($membersData as $member) {
            $members[] = new MemberDataWrapper(
                $member['name'],
                $member['slug'],
                $member['image'],
                in_array($member['id'], $idsOfMembersWithUpdates, true),
                $member['patron']
            );
        }

        return $members;
    }
}
