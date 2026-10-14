<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateServicios extends Migration
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
            'cliente_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'codigo_servicio' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'tipo_servicio' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'INTERNET_FIJO',
            ],
            'nombre_plan' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'velocidad_mbps' => [
                'type'       => 'INT',
                'constraint' => 5,
                'unsigned'   => true,
            ],
            'direccion_instalacion' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'distrito' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'estado' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'ACTIVO',
                    'SUSPENDIDO',
                    'CANCELADO',
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('codigo_servicio');
        $this->forge->addKey('cliente_id');

        $this->forge->addForeignKey(
            'cliente_id',
            'usuarios',
            'id',
            'RESTRICT',
            'CASCADE'
        );
        $this->forge->createTable('servicios');
    }
    public function down()
    {
        $this->forge->dropTable('servicios');
    }
}