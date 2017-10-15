<?php
/**
 * @author Cann(imcnny@gmail.com)
 */

namespace cann\yii\log\commands;

use Yii;
use yii\console\Controller;
use yii\di\Instance;
use yii\redis\Connection as redisConnection;
use yii\db\Connection as dbConnection;
use yii\helpers\Console;
use cann\yii\log\CreateTable;

class LogHandler extends Controller
{
    const KEY_PREFIX = 'LOG:';

    public $redis = 'redis';
    public $db    = 'db';

    public function init() {
        parent::init();
        $this->redis = Instance::ensure($this->redis, redisConnection::className());
        $this->db    = Instance::ensure($this->db, dbConnection::className());
    }

    protected function getTableName($logName)
    {
        $logName = str_replace(self::KEY_PREFIX, '', $logName);
        return "{{%log_{$logName}}}";
    }

    protected function getFullLogName($logName)
    {
        return self::KEY_PREFIX . $logName;
    }

    protected function getLogNameByFullName($fullLogName)
    {
        return str_replace(self::KEY_PREFIX, '', $fullLogName);
    }

    /**
     * Stores Redis log messages to DB.
     */
    public function actionExportToDb($logName)
    {
        $tableName = $this->getTableName($logName);

        $hasTable = (bool) ($this->db->createCommand("SHOW TABLES LIKE '%log_" . $logName . "'")->queryOne());

        // Create Table If Not Exist
        if (! $hasTable) {
            CreateTable::run($tableName);
        }

        $okCount = 0;

        // 每次处理1000条
        for ($i = 0; $i < 1000; $i++) {

            $data = $this->redis->lpop($this->getFullLogName($logName));

            if (! $data = json_decode($data, true)) {
                break;
            }

            try {
                $this->db->createCommand()->insert($tableName, [
                    'level'      => $data['level'],
                    'category'   => $data['category'],
                    'prefix'     => $data['prefix'],
                    'message'    => $data['message'],
                    'created_at' => $data['created_at'],
                ])->execute();
            } catch (yii\db\Exception $e) {
                $this->redis->rpush($this->getFullLogName($logName), json_encode($data));
                echo $e->getMessage() . PHP_EOL;
                break;
            }

            $okCount++;
        }

        echo $this->ansiFormat("Redis Log：{$logName} Is Complete。 Count：{$okCount}", Console::FG_GREEN) . PHP_EOL;

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * Stores All Redis log messages to DB.
     */
    public function actionExportAllToDb()
    {
        $fullLogNames = $this->redis->keys(self::KEY_PREFIX . '*');

        if (! $fullLogNames) {
            return false;
        }

        $result = [];

        foreach ($fullLogNames as $fullLogName) {

            $logName = $this->getLogNameByFullName($fullLogName);

            if ($okCount = self::actionExportToDb($logName)) {
                $result[$logName] = $okCount;
            }
        }

        return self::EXIT_CODE_NORMAL;
    }
}