<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%auth}}`.
 * Has foreign keys to the tables:
 *
 * - `{{%user}}`
 */
class m220416_081845_create_auth_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%auth}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'source' => $this->string()->notNull(),
            'source_id' => $this->string()->notNull(),
        ]);

        // creates index for column `user_id`
        $this->createIndex(
            '{{%idx-auth-user_id}}',
            '{{%auth}}',
            'user_id'
        );

        // add foreign key for table `{{%user}}`
        $this->addForeignKey(
            '{{%fk-auth-user_id}}',
            '{{%auth}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // drops foreign key for table `{{%user}}`
        $this->dropForeignKey(
            '{{%fk-auth-user_id}}',
            '{{%auth}}'
        );

        // drops index for column `user_id`
        $this->dropIndex(
            '{{%idx-auth-user_id}}',
            '{{%auth}}'
        );

        $this->dropTable('{{%auth}}');
    }
}
