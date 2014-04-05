<?php

/**
 * This file is part of the Engage360d package bundles.
 *
 */

namespace Engage360d\Bundle\SecurityBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Form\Exception\InvalidPropertyPathException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as Controller;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use JMS\SecurityExtraBundle\Annotation\Secure;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwnerInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

use HWI\Bundle\OAuthBundle\Controller\ConnectController;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\VkontakteResourceOwner;
use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\FacebookResourceOwner;

/**
 * Process form auth to oauth.
 *
 * @author Andrey Linko <AndreyLinko@gmail.com>
 */
class SecurityController extends ConnectController
{
    public function connectAction(Request $request)
    {
        $error = $this->getErrorForRequest($request);

        $userInformation = $this
            ->getResourceOwnerByName($error->getResourceOwnerName())
            ->getUserInformation($error->getRawToken())
        ;

        $responseOwner = $this->getResourceOwnerByName($error->getResourceOwnerName());

        return $this->container->get('templating')->renderResponse('Engage360dTakedaUserBundle:Account:connect.html.twig', array(
          'user' => $this->getUserByResourceOwner(
              $responseOwner,
              $responseOwner->getUserInformation($error->getRawToken())
            )
        ));
    }
    
    protected function getUserByResourceOwner($resourceOwner, $userInformation)
    {
        $response = $userInformation->getResponse();
        $user = array(
            'email' => $userInformation->getEmail(),
        );

        if ($resourceOwner instanceof FacebookResourceOwner) {
            $birthday = \DateTime::createFromFormat('d/m/Y' , $response['birthday']);
            $user['facebookId'] = $userInformation->getUsername();
            $user['firstname'] = $response['first_name'];
            $user['birthday'] = $birthday->format('Y-m-d');
        } else if ($resourceOwner instanceof VkontakteResourceOwner) {
            $user['vkontakteId'] = $userInformation->getUsername();
            $user['firstname'] = $response['response'][0]['first_name'];
        }

        return $user;
    }

    public function successAction()
    {
        return new Response();
    }
}
