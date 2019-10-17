<?php
namespace Translate5\FrontEndMessageBus;
use Translate5\FrontEndMessageBus\Exceptions\ConfigurationException;
/**
 * Wrapper class to the configuration of the socket and message server
 */
class Configuration {
    protected $config;
    public function __construct(string $configFile) {
        if(!file_exists($configFile) || !is_readable($configFile)) {
            throw new ConfigurationException('Config file "'.$configFile.'" is not readable or does not exist! See config.php.example');
        }
        include $configFile;
        /* @var $configuration array */
        if(empty($configuration)) {
            throw new ConfigurationException('Config file "'.$configFile.'" does not contain an array $configuration with the needed configuration values. See config.php.example');
        }
        $this->config = json_decode(json_encode($configuration), null, JSON_FORCE_OBJECT);
    }
    
    /**
     * returns the given config value by name
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->config->$name;
    }
}