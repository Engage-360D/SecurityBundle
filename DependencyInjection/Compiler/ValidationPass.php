<?php

/**
 * This file is part of the Engage360d package bundles.
 *
 */

namespace Engage360d\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Config\Resource\FileResource;

class ValidationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('validator.mapping.loader.yaml_files_loader.mapping_files')) {
            $files = array();
        } else {
            $files = $container->getParameter('validator.mapping.loader.yaml_files_loader.mapping_files');
        }

        $validationFile = __DIR__ . '/../../Resources/config/validation/user.yml';

        if (is_file($validationFile)) {
            $files[] = realpath($validationFile);
            $container->addResource(new FileResource($validationFile));
        }

        $container->setParameter('validator.mapping.loader.yaml_files_loader.mapping_files', $files);
    }
}
