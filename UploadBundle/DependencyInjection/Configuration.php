<?php

namespace Site\UploadBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('site_upload');

        $rootNode
            ->children()
                ->scalarNode('web_dir')->end()
                ->scalarNode('temp_upload_dir')->end()
                ->arrayNode('types')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->end()
                            ->scalarNode('format')->end()
                            ->arrayNode('mime_type')
                                ->prototype('scalar')->end()
                            ->end()
                            ->scalarNode('upload_dir')->end()
                            ->scalarNode('max_size')->end()
                            ->scalarNode('main_action')->end()
                            ->arrayNode('thumbnails')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('width')->end()
                                        ->scalarNode('height')->end()
                                        ->scalarNode('action')->end()
                                        ->scalarNode('watermark')->end()
                                        ->scalarNode('position')->end()
                                        ->scalarNode('padding-x')->end()
                                        ->scalarNode('padding-y')->end()
                                        ->scalarNode('opacity')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
