<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'key' => [
                'type' => 'varchar',
                'constraint' => 100,
                'unique' => true,
            ],
            'value' => [
                'type' => 'text',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('key');
        $this->forge->createTable('settings');
    }

    public function down()
    {
        $this->forge->dropTable('settings');
    }
}
?>
