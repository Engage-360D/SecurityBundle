<?php

/**
 * This file is part of the Engage360d package bundles.
 *
 */

namespace Engage360d\Bundle\SecurityBundle\Entity\User;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManager as BaseUserManager;
use FOS\UserBundle\Util\CanonicalizerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use FOS\OAuthServerBundle\Model\ClientInterface;

use FOS\UserBundle\Doctrine\UserManager as BaseManager;

/**
 * User manager.
 *
 */
class UserManager extends BaseManager
{
    /**
     * Constructor.
     *
     * @param EncoderFactoryInterface $encoderFactory
     * @param string                  $algorithm
     * @param CanonicalizerInterface  $usernameCanonicalizer
     * @param CanonicalizerInterface  $emailCanonicalizer
     * @param EntityManager           $em
     * @param string                  $class
     */
    public function __construct(
        EncoderFactoryInterface $encoderFactory,
        CanonicalizerInterface $usernameCanonicalizer, 
        CanonicalizerInterface $emailCanonicalizer,
        EntityManager $em,
        $class)
    {
        parent::__construct($encoderFactory, $usernameCanonicalizer, $emailCanonicalizer, $em, $class );

        $this->encoderFactory = $encoderFactory;
        $this->usernameCanonicalizer = $usernameCanonicalizer;
        $this->emailCanonicalizer = $emailCanonicalizer;
    }

    public function findById($id)
    {
        return $this->repository->findOneBy(array('id' => $id));
    }

    public function getPage($page = 1, $limit = 25, $select = 'u')
    {
        return $this->repository->findAll();
    }
}