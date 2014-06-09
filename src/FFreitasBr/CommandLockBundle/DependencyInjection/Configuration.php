<?php

namespace FFreitasBr\CommandLockBundle\DependencyInjection;

use FFreitasBr\CommandLockBundle\Traits\NamesDefinitionsTrait;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package FFreitasBr\CommandLockBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    use NamesDefinitionsTrait;

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->configurationRootName);

        $rootNode
            ->children()
                ->scalarNode($this->pidDirectorySetting)
                    ->info('Define where the pid files will be stored')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode($this->exceptionsListSetting)
                    ->info('Define a list of exceptions, who will not be locked.')
                    ->defaultValue(array())
                        ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode($this->maxLifeTimesListSetting)
                    ->info('Define a list of values [command , time] to define the max life time of thecommand')
                    ->defaultValue(array())
                    ->prototype('array')
                        ->children()
                            ->scalarNode('command')->end()
                            ->integerNode('time')->end()
                        ->end() 
                    ->end()
                ->end()
                ->integerNode($this->defaultMaxLifeTimeSetting)
                    ->info('Define the maximum life time for a command')
                    ->defaultValue(82800)//23*3600
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
