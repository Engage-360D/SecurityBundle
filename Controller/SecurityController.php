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

/**
 * Process form auth to oauth.
 *
 * @author Andrey Linko <AndreyLinko@gmail.com>
 */
class SecurityController extends Controller
{
    public function successAction()
    {
        $clientManager = $this->container->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setRedirectUris(array('/admin'));
        $client->setAllowedGrantTypes(array('token', 'authorization_code'));
        $clientManager->updateClient($client);

        $response = new Response(json_encode(array('client_id' => $client->getPublicId())));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }

    public function checkFacebookAction()
    {
    }

    public function loginFacebookAction()
    {
    }

    public function connectFacebookWithAccountAction()
    {
    }
}
