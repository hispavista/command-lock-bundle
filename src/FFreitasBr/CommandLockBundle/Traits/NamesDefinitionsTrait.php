<?php

namespace FFreitasBr\CommandLockBundle\Traits;

/**
 * Class NamesDefinitionsTrait
 *
 * @package FFreitasBr\CommandLockBundle\Traits
 */
trait NamesDefinitionsTrait
{
    protected $configurationsParameterKey = 'command_lock.configuration';
    protected $pidDirectorySetting        = 'pid_directory';
    protected $exceptionsListSetting      = 'exceptions';
    protected $maxLifeTimesListSetting    = 'max_life_times';
    protected $configurationRootName      = 'command_lock';
    protected $defaultMaxLifeTimeSetting  = 'default_max_life_time';
}
