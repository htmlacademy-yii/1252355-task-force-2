<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%task_file}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%task}}`
 */
class m220331_102716_create_task_file_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%task_file}}', [
            'id' => $this->primaryKey(),
            'task_id' => $this->integer()->notNull(),
            'path' => $this->string(255)->notNull(),
            'original_name' => $this->string(255)->notNull(),
            'date_created' => $this->datetime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // creates index for column `task_id`
        $this->createIndex(
            '{{%idx-task_file-task_id}}',
            '{{%task_file}}',
            'task_id'
        );

        // add foreign key for table `{{%task}}`
        $this->addForeignKey(
            '{{%fk-task_file-task_id}}',
            '{{%task_file}}',
            'task_id',
            '{{%task}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%task}}`
        $this->dropForeignKey(
            '{{%fk-task_file-task_id}}',
            '{{%task_file}}'
        );

        // drops index for column `task_id`
        $this->dropIndex(
            '{{%idx-task_file-task_id}}',
            '{{%task_file}}'
        );

        $this->dropTable('{{%task_file}}');
    }
}
