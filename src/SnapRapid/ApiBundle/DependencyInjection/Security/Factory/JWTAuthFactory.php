<?php

namespace SnapRapid\ApiBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class JWTAuthFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'dao_auth_provider.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('dao_auth_provider'))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(2, $id);

        $listenerId = 'security.authentication.listener.jwt_auth.'.$id;
        $container
            ->setDefinition($listenerId, new DefinitionDecorator('jwt_auth_listener'))
            ->replaceArgument(2, $id)
            ->replaceArgument(5, $config);

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'jwt_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('username_parameter')
                    ->defaultValue('username')
                ->end()
                ->scalarNode('password_parameter')
                    ->defaultValue('password')
                ->end()
            ->end();
    }
}
