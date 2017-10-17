<?php
/**
 * @author Cann(imcnny@gmail.com)
 */

namespace cann\yii\log;

use yii\base\BootstrapInterface;
use cann\yii\log\commands\LogHandler;
use yii\console\Application as ConsoleApp;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap['log-handler'] = [
                'class' => LogHandler::class,
            ];
        }
    }
}
