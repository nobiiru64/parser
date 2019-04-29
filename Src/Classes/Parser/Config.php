<?php
namespace Parser;
use Parser\EnvException;

use Dotenv\Dotenv;

class Config {

    public $config;


    public function __construct($env)
    {
        if (!is_file(realpath($env.'/.env')))
            throw new EnvException("Файл .env не найден");

        $envLoaded = Dotenv::create($env)->load();

        if (!$envLoaded)
            throw new EnvException("Файл .env не загрузился");

        $this->config = include($env . "/Src/config.php");


    }

    public function get(){
        return (object) $this->config;
    }
}
