#### Redis Logger Of Yii2

##### 安装

```
    composer require cann/yii2-redis-log
```

##### 配置

```
    // ***
    'components' => [
        'log' => [
            'targets' => [
                'redis' => [
                    'class' => 'cann\yii\log\RedisTarget',
                    'redis' => 'redis',
                    'key' => 'default',
                    'levels' => ['trace', 'info'],
                    'categories' => ['yii\*'],
                ]
            ]
        ],
    ]
```

>`key` 用于区分不同的Redis队列

##### 将redis中的日志落地至数据库

如果要将 `key` 为`default`的Redis Log导入数据库：

```
    yii log-handler/export-to-db default
```

如果要将所有Redis Log导入数据库：

```
    yii log-handler/export-all-to-db
```

你可以将该命令写入crontab定期执行

>log handler会自动创建log表，表名默认为 {db prefix}\_log\_{key}

