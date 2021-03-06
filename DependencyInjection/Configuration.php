<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonata_notification')->children();

        $rootNode
            ->scalarNode('backend')->defaultValue('sonata.notification.backend.runtime')->end()
            ->arrayNode('backends')
                ->children()
                    ->arrayNode('rabbitmq')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('exchange')->cannotBeEmpty()->isRequired()->end()
                            ->scalarNode('queue')->cannotBeEmpty()->isRequired()->end()
                            ->arrayNode('connection')
                                ->children()
                                    ->scalarNode('host')->defaultValue('localhost')->end()
                                    ->scalarNode('port')->defaultValue(5672)->end()
                                    ->scalarNode('user')->defaultValue('guest')->end()
                                    ->scalarNode('pass')->defaultValue('guest')->end()
                                    ->scalarNode('vhost')->defaultValue('guest')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('consumers')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('swift_mailer')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('path')->defaultValue('%kernel.root_dir%/../vendor/swiftmailer')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('class')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('message')->defaultValue('Sonata\\NotificationBundle\\Entity\\Message')->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
