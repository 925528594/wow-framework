<?php

namespace Negan\Foundation;

use Negan\Routing\Route;

class App
{
    const VERSION = '1.0.2';
    protected $basePath;
    protected $baseConfigPath;

    public function __construct($basePath = null)
    {
        if ( $basePath ) {
            $basePath = rtrim($basePath, '\/');
            $this->setBasePath($basePath);
            $this->setBaseConfigPath($basePath);

            $this->loadConfigApp();
            $this->loadConfigDatabase();

            $this->run();
        }
    }

    private function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        env( 'BASE_PATH', $this->basePath );
    }

    private function setBaseConfigPath($basePath)
    {
        $this->baseConfigPath = $basePath . DIRECTORY_SEPARATOR . 'config';
        env( 'BASE_CONFIG_PATH', $this->baseConfigPath );
    }

    private function loadConfigApp()
    {
        $profile = $this->baseConfigPath . DIRECTORY_SEPARATOR . 'app.php';
        $this->checkFile( $profile );
        env( 'CONFIG_APP', require( $profile ) );
    }

    private function loadConfigDatabase()
    {
        $profile = $this->baseConfigPath . DIRECTORY_SEPARATOR . 'database.php';
        $this->checkFile( $profile );
        env( 'CONFIG_DATABASE', require( $profile ) );
    }

    private function checkFile($file)
    {
        if ( !file_exists($file) ) {
            exit( 'config profile missing(' . basename($file) . ')' );
        }
    }

    private function run()
    {
        Config::init();
        Route::init();
    }

}
