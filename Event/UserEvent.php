<?php

/**
 * This file is part of the Engage360d package bundles.
 *
 */

namespace Engage360d\Bundle\SecurityBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use FOS\UserBundle\Model\UserInterface;

class UserEvent extends Event
{
    private $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
