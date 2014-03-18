<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            
            new JMS\SerializerBundle\JMSSerializerBundle($this),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new FOS\FacebookBundle\FOSFacebookBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new FOS\OAuthServerBundle\FOSOAuthServerBundle(),
            
            new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
            new Sensio\Bundle\DistributionBundle\SensioDistributionBundle(),
            new Engage360d\Bundle\RestBundle\Engage360dRestBundle(),
            new Engage360d\Bundle\SecurityBundle\Engage360dSecurityBundle(),
        );

        return $bundles;
    }
    
    public function getCacheDir()
    {
        return realpath(__DIR__.'/../..') . '/.tmp/cache';
    }

    public function getLogDir()
    {
        return realpath(__DIR__.'/../..') . '/.tmp/logs';
    }
    
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config.yml');
    }

    protected function getKernelParameters()
    {
        return array_merge(
            parent::getKernelParameters(),
            array(
                'kernel.vendor_dir'        => realpath($this->rootDir . '/../../vendor'),
            )
        );
    }
}
