<?php
/**
 * @author Cann(imcnny@gmail.com)
 */

namespace cann\yii\log\commands;

use yii\console\Controller;
use yii\di\Instance;
use yii\redis\Connection as redisConnection;
use yii\db\Connection as dbConnection;
use yii\helpers\Console;
use cann\yii\log\CreateTable;
use yii\helpers\Inflector;

class LogHandler extends Controller
{
    const KEY_PREFIX = 'LOG:';

    public $redis = 'redis';
    public $db = 'db';

    /**
     * Set Components, Because Init() Can't Receive Options
     */
    public function beforeAction($action)
    {
        $this->redis = Instance::ensure($this->redis, redisConnection::className());
        $this->db    = Instance::ensure($this->db, dbConnection::className());
        return true;
    }

    /**
     * You can customize the component id，
     * For example: yii log-handler/export-all-to-db --db=db2 --redis=redis2
     */
    public function options($actionID)
    {
        return ['redis', 'db'];
    }

    protected function getTableName($logName)
    {
        $logName = Inflector::camel2id(str_replace(self::KEY_PREFIX, '', $logName), '_');
        return "{{%log_{$logName}}}";
    }

    protected function getFullLogName($logName)
    {
        return self::KEY_PREFIX . $logName;
    }

    /**
     * Stores Redis log messages to DB.
     */
    public function actionExportToDb($logName, $count = 1000)
    {
        $tableName = $this->getTableName($logName);

        $hasTable = (bool) ($this->db->createCommand("SHOW TABLES LIKE '%log_" . $logName . "'")->queryOne());

        // Create Table If Not Exist
        if (! $hasTable) {
            CreateTable::run($tableName);
        }

        $insertArr = [];

        for ($i = 0; $i < $count; $i++) {

            $data = $this->redis->lpop($this->getFullLogName($logName));

            if (! $data = json_decode($data, true)) {
                break;
            }

            $insertArr[] = [
                'level'      => $data['level'],
                'category'   => $data['category'],
                'prefix'     => $data['prefix'],
                'message'    => $data['message'],
                'created_at' => $data['created_at'],
            ];
        }

        try {
            $okCount = $this->db->createCommand()->batchInsert($tableName, [
                'level', 'category', 'prefix', 'message', 'created_at'
            ], $insertArr)->execute();
        } catch (yii\db\Exception $e) {
            // do nothing
        }

        echo $this->ansiFormat("Redis Log：{$logName} Is Complete。 Count：{$okCount}", Console::FG_GREEN) . PHP_EOL;

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Stores All Redis log messages to DB.
     */
    public function actionExportAllToDb($count = 1000)
    {
        $fullLogNames = $this->redis->keys(self::KEY_PREFIX . '*');

        if (! $fullLogNames) {
            return false;
        }

        $result = [];

        foreach ($fullLogNames as $fullLogName) {

            $logName = str_replace(self::KEY_PREFIX, '', $fullLogName);

            if ($okCount = self::actionExportToDb($logName, $count)) {
                $result[$logName] = $okCount;
            }
        }

        return self::EXIT_CODE_NORMAL;
    }
}
