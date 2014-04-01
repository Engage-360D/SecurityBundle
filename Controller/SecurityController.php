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
        return new Response();
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
