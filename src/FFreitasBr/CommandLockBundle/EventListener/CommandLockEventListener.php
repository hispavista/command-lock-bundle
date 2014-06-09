<?php

namespace FFreitasBr\CommandLockBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use FFreitasBr\CommandLockBundle\Exception\CommandAlreadyRunningException;
use FFreitasBr\CommandLockBundle\Traits\NamesDefinitionsTrait;
use Psr\Log\LoggerInterface;

/**
 * Class CommandLockEventListener
 *
 * @package FFreitasBr\CommandLockBundle\EventListener
 */
class CommandLockEventListener extends ContainerAware
{
    use NamesDefinitionsTrait;

    /**
     * @var null|string
     */
    protected $pidDirectory = null;

    /**
     * @var array
     */
    protected $exceptionsList = array();

    /**
     * @var array
     */
    protected $maxLifeTimes = array();
    
    /**
     * @var null|string
     */
    protected $pidFile = null;
    
    /**
     * @var null|integer
     */
    protected $defaultMaxLifeTime=null;
    
    /**
     * @var LoggerInterface
     */
    protected $logger=null;

    /**
     * @param ContainerInterface $container
     *
     * @return self
     */
    public function __construct(ContainerInterface $container)
    {
        // set container
        $this->setContainer($container);
        // get the pid directory and store in self
        $this->pidDirectory = $container->getParameter($this->configurationsParameterKey)[$this->pidDirectorySetting];
        // get the configured exceptions list
        $this->exceptionsList = $container->getParameter($this->configurationsParameterKey)[$this->exceptionsListSetting];
        // get the default max life time
        $this->defaultMaxLifeTime = $container->getParameter($this->configurationsParameterKey)[$this->defaultMaxLifeTimeSetting];
        //Get the max life times of the commands
        $this->maxLifeTimes=$container->getParameter($this->configurationsParameterKey)[$this->maxLifeTimesListSetting];
        // get the logger
        $this->logger=$container->get('logger');
    }

    /**
     * @param ConsoleCommandEvent $event
     *
     * @return void
     * @throws CommandAlreadyRunningException
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        // generate pid file name
        $commandName = $event->getCommand()->getName();
        // check for exceptions
        if (in_array($commandName, $this->exceptionsList)) {
            return;
        }
        $clearedCommandName = $this->cleanString($commandName);
        $pidFile = $this->pidFile = $this->pidDirectory . "/{$clearedCommandName}.pid";
        // check if command is already executing
        if (file_exists($pidFile)) {
            $this->logger->info("$commandName -> File exists: $pidFile");
            $pidOfRunningCommand = file_get_contents($pidFile);
            if (posix_getpgid($pidOfRunningCommand) !== false) {
                //Get the file change/creation date
                $lifeTime= time()-filectime($pidFile)  ;
                $this->logger->info("$commandName -> Command max life time : ".$this->defaultMaxLifeTime);
                $this->logger->info("$commandName -> Command life time : $lifeTime");
                
                //Check if the process life time is over
                if ($lifeTime<$this->getMaxLifeTime($commandName)){
                    $this->logger->warn("$commandName -> Command already runing");
                    throw (new CommandAlreadyRunningException)
                        ->setCommandName($commandName)
                        ->setPidNumber($pidOfRunningCommand);
                }
                else{
                    //kill the command 
                    $this->logger->warn("$commandName -> Killing The command");
					if (!posix_kill ($pidOfRunningCommand,9)){
                        $this->logger->error("$commandName -> Error killing the command");
                        throw (new CommandAlreadyRunningException)
                            ->setCommandName($commandName)
                            ->setPidNumber($pidOfRunningCommand);
                    }
                }
            }
            // pid file exist but the command is not running anymore
            unlink($pidFile);
        }
        // if is not already executing create pid file
        file_put_contents($pidFile, getmypid());
        $this->logger->info("$commandName -> File created: $pidFile");
        // register shutdown function to remove pid file in case of unexpected exit
        register_shutdown_function(array($this, 'shutDown'), null, $pidFile);
    }

    /**
     * @param ConsoleTerminateEvent $event
     *
     * @return void
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        $commandName = $event->getCommand()->getName();
        if (isset($this->pidFile) && file_exists($this->pidFile)) {
            unlink($this->pidFile);
            $this->logger->info("$commandName -> File removed: $this->pidFile");
        }
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    public function cleanString($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }

    /**
     * @param null|int $pidFile
     */
    public function shutDown($pidFile = null)
    {
        if (!isset($pidFile) && isset($this->pidFile)) {
            $pidFile = $this->pidFile;
        }
        if (file_exists($pidFile)) {
            unlink($pidFile);
            $this->logger->info("File removed by shutdown function: $this->pidFile");
        }
    }
    
    /**
     * @param string $commandName
     * @return int
     */
    protected function getMaxLifeTime($commandName){
        $time=$this->defaultMaxLifeTime;
        foreach ($this->maxLifeTimes as $elem){
            if ($elem['command']==$commandName){
                $time = $elem['time'];
                break;
            }
        }
        return $time;
    }
}
