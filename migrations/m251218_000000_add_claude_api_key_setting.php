<?php

use yii\db\Migration;

/**
 * Добавляет настройку для Claude API ключа
 */
class m251218_000000_add_claude_api_key_setting extends Migration
{
    public function safeUp()
    {
        $this->insert('settings', [
            'title' => 'Claude API Key',
            'key' => 'claude-api-key',
            'value' => '', // API ключ нужно будет добавить вручную
            'created' => date('Y-m-d H:i:s'),
        ]);
    }

    public function safeDown()
    {
        $this->delete('settings', ['key' => 'claude-api-key']);
    }
}
