<?php

namespace SnapRapid\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

class ValidatorPass implements CompilerPassInterface
{
    /**
     * Load the yml validation mapping files from the Core
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $validatorBuilder = $container->getDefinition('validator.builder');
        $validatorFiles   = [];
        $finder           = new Finder();
        $finder->files()->in(
            $container->getParameter('kernel.root_dir').'/../src/SnapRapid/Core/Validation/mapping'
        );

        foreach ($finder as $file) {
            $validatorFiles[] = $file->getRealPath();
        }

        $validatorBuilder->addMethodCall('addYamlMappings', [$validatorFiles]);
    }
}
