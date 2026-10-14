<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateCategoriasIncidencia extends Migration
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
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'descripcion' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'prioridad_default' => [
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
                    'ACTIVO',
                    'INACTIVO',
                ],
                'default' => 'ACTIVO',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre');
        $this->forge->createTable('categorias_incidencia');
    }
    public function down()
    {
        $this->forge->dropTable('categorias_incidencia');
    }
}