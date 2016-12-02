<?php

namespace SnapRapid\ApiBundle;

use SnapRapid\ApiBundle\DependencyInjection\Compiler\ValidatorPass;
use SnapRapid\ApiBundle\DependencyInjection\Security\Factory\JWTAuthFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SnapRapidApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ValidatorPass());

        /** @var SecurityExtension $securityExtension */
        $securityExtension = $container->getExtension('security');
        $securityExtension->addSecurityListenerFactory(new JWTAuthFactory());
    }
}
