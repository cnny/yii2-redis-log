<?php
/**
 * @author Cann(imcnny@gmail.com)
 */

namespace cann\yii\log;

use yii\db\Migration;

class CreateTable
{
    public static function run($tableName)
    {
        $migration = new Migration();

        $migration->createTable($tableName, [
            'id'         => $migration->primaryKey(),
            'level'      => $migration->string(200)->notNull(),
            'category'   => $migration->string(200)->notNull(),
            'prefix'     => $migration->string(50)->notNull(),
            'message'    => 'LONGTEXT',
            'created_at' => $migration->timestamp()
        ], "COLLATE='utf8mb4_unicode_ci'");
    }
}
