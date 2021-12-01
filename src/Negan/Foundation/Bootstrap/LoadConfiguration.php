<?php

namespace Negan\Foundation\Bootstrap;

use Exception;
use Negan\Config\Repository;
use Negan\Foundation\Application;

class LoadConfiguration
{
    /**
     * 引导给定的应用程序
     * 实际就是 注册绑定 配置仓库实例, 并载入配置到 配置仓库实例 中
     *
     * @param \Negan\Foundation\Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $items = [];

        $app->instance('config', $config = new Repository($items));

        //这里可以做配置缓存读取, 此处未实现

        $this->loadConfigurationFiles($app, $config);

        date_default_timezone_set($config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');
    }

    /**
     * 从所有文件加载配置项
     *
     * @param \Negan\Foundation\Application $app
     * @param \Negan\Config\Repository $repository
     * @return void
     *
     * @throws \Exception
     */
    protected function loadConfigurationFiles(Application $app, Repository $repository)
    {
        $files = $this->getConfigurationFiles($app);

        if (! isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

        foreach ($files as $key => $path) {
            $repository->set($key, require $path);
        }
    }

    /**
     * 获取应用程序的所有配置文件
     *
     * @param \Negan\Foundation\Application $app
     * @return array
     */
    protected function getConfigurationFiles(Application $app)
    {
        $files = [];

        $configPath = realpath($app->configPath());

        $allFilePath = glob($configPath.DIRECTORY_SEPARATOR.'*.php');

        foreach ($allFilePath as $filePath) {
            $files[pathinfo($filePath)['filename']] = $filePath;
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

}
