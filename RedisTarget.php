<?php
/**
 * @author Cann(imcnny@gmail.com)
 */

namespace cann\yii\log;

use yii\di\Instance;
use yii\log\Target;
use yii\redis\Connection;
use yii\helpers\VarDumper;

/**
 * RedisTarget store log messages in a redis list.
 */
class RedisTarget extends Target
{
    /**
     * @var Connection|array|string the Redis connection object or a configuration array for creating the object, or the application component ID of the Redis connection.
     */
    public $redis = 'redis';

    /**
     * @var string key of the Redis list to store log messages. Default to "log"
     */
    public $key = 'log';

    const KEY_PREFIX = 'LOG:';

    /**
     * Initializes the RedisTarget component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid Redis connection.
     * @throws InvalidConfigException if [[redis]] is invalid.
     */
    public function init() {
        parent::init();
        $this->redis = Instance::ensure($this->redis, Connection::className());
    }

    /**
     * Stores log messages to Redis.
     */
    public function export() {
        foreach ($this->messages as $message) {

            list($text, $level, $category, $timestamp) = $message;

            if (! is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    $text = (string) $text;
                } else {
                    $text = VarDumper::export($text);
                }
            }

            $content = json_encode([
                'message'    => $text,
                'level'      => $level,
                'prefix'     => $this->getMessagePrefix($message),
                'category'   => $category,
                'created_at' => date('Y-m-d H-i-s', $timestamp),
            ]);

            $this->redis->executeCommand('RPUSH', [self::KEY_PREFIX . $this->key, $content]);
        }
    }
}