<?php

class Application
{
    private $configFile = "../config.ini";
    public $config;
    
    public function __construct() {
        $this->config = parse_ini_file($this->configFile, true);
    }
}
