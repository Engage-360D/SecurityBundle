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

// Todo: Move to TakedaUserBundle

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
    
    /**
     * Connects a user to a given account if the user is logged in and connect is enabled.
     *
     * @param Request $request The active request.
     * @param string  $service Name of the resource owner to connect to.
     *
     * @throws \Exception
     *
     * @return Response
     *
     * @throws NotFoundHttpException if `connect` functionality was not enabled
     * @throws AccessDeniedException if no user is authenticated
     */
    public function connectServiceAction(Request $request, $service)
    {
        $hasUser = $this->container->get('security.context')->isGranted('IS_AUTHENTICATED_REMEMBERED');
        if (!$hasUser) {
            //redirect to register
            throw new AccessDeniedException('Cannot connect an account.');
        }

        // Get the data from the resource owner
        $resourceOwner = $this->getResourceOwnerByName($service);

        $session = $request->getSession();
        $key = $request->query->get('key', time());

        if ($resourceOwner->handles($request)) {
            $accessToken = $resourceOwner->getAccessToken(
                $request,
                $this->generate('hwi_oauth_connect_service', array('service' => $service), true)
            );

            // save in session
            $session->set('_hwi_oauth.connect_confirmation.'.$key, $accessToken);
        } else {
            $accessToken = $session->get('_hwi_oauth.connect_confirmation.'.$key);
        }

        $userInformation = $resourceOwner->getUserInformation($accessToken);

        $currentToken = $this->container->get('security.context')->getToken();
        $currentUser  = $currentToken->getUser();

        $this->container
            ->get('engage360d_security.oauth.provider')
            ->connect($currentUser, $userInformation);

        $url = $this->container->get('router')->generate('engage360d_takeda_user_modal_login_success');
        return new RedirectResponse($url);
    }
    
    protected function getUserByResourceOwner($resourceOwner, $userInformation)
    {
        $response = $userInformation->getResponse();
        $user = array(
            'email' => $userInformation->getEmail(),
        );

        if ($resourceOwner instanceof FacebookResourceOwner) {
            $user['facebookId'] = $userInformation->getUsername();
            $user['firstname'] = $response['first_name'];
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
