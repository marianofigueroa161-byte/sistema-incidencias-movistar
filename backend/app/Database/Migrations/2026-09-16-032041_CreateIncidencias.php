<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateIncidencias extends Migration
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
            'codigo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'cliente_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'servicio_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'categoria_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'tecnico_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'asunto' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'descripcion' => [
                'type' => 'TEXT',
            ],
            'evidencia' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'prioridad' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'BAJA',
                    'MEDIA',
                    'ALTA',
                    'CRITICA',
                ],
                'default' => 'MEDIA',
            ],
            'estado' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'REGISTRADO',
                    'ASIGNADO',
                    'EN_PROCESO',
                    'SOLUCIONADO',
                    'CERRADO',
                    'CANCELADO',
                ],
                'default' => 'REGISTRADO',
            ],
            'diagnostico' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'solucion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'fecha_asignacion' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'fecha_inicio' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'fecha_solucion' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'fecha_cierre' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo');
        $this->forge->addKey('cliente_id');
        $this->forge->addKey('servicio_id');
        $this->forge->addKey('categoria_id');
        $this->forge->addKey('tecnico_id');
        $this->forge->addKey('estado');
        $this->forge->addKey('prioridad');
        $this->forge->addForeignKey(
            'cliente_id',
            'usuarios',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'servicio_id',
            'servicios',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'categoria_id',
            'categorias_incidencia',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->forge->addForeignKey(
            'tecnico_id',
            'usuarios',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->forge->createTable('incidencias');
    }
    public function down()
    {
        $this->forge->dropTable('incidencias');
    }
}