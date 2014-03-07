<?php

namespace Engage360d\Bundle\SecurityBundle\EventListener;

use FOS\OAuthServerBundle\Event\OAuthEvent;

class OAuthEventListener
{
    public function onPreAuthorizationProcess(OAuthEvent $event)
    {
        if ($user = $event->getUser()) {
            $event->setAuthorizedClient(true);
        }
    }

    public function onPostAuthorizationProcess(OAuthEvent $event)
    {
        if ($event->isAuthorizedClient()) {
            if (null !== $client = $event->getClient()) {
                $user = $this->getUser($event);
                $user->addClient($client);
                $user->save();
            }
        }
    }
}