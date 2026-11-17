<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificaciones extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'incidencia_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'mensaje' => [
                'type' => 'TEXT',
            ],
            'estado_nuevo' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
            ],
            'leida_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['usuario_id', 'leida_at']);
        $this->forge->addKey('incidencia_id');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('incidencia_id', 'incidencias', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('notificaciones');
    }

    public function down()
    {
        $this->forge->dropTable('notificaciones');
    }
}