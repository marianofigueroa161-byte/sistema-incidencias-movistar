<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateHistorialIncidencias extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'incidencia_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'usuario_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'estado_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'estado_nuevo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'comentario' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('incidencia_id');
        $this->forge->addKey('usuario_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey(
            'incidencia_id',
            'incidencias',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'usuario_id',
            'usuarios',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->forge->createTable('historial_incidencias');
    }
    public function down()
    {
        $this->forge->dropTable('historial_incidencias');
    }
}