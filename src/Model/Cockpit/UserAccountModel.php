<?php
declare(strict_types=1);

namespace App\Model\Cockpit;

use App\Entity\User;
use App\Entity\UserInfo;

class UserAccountModel
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var UserInfo|null
     */
    private $userInfo;

    public function __construct(
        User $user,
        UserInfo $userInfo
    ) {
        $this->user = $user;
        $this->userInfo = $userInfo;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): UserAccountModel
    {
        $this->user = $user;
        return $this;
    }

    public function getUserInfo(): UserInfo
    {
        return $this->userInfo;
    }

    public function setUserInfo(UserInfo $userInfo): UserAccountModel
    {
        $this->userInfo = $userInfo;
        return $this;
    }
}
