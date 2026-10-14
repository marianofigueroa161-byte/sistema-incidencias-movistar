<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreateUsuarios extends Migration
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
            'rol_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
            ],
            'tipo_documento' => [
                'type'       => 'ENUM',
                'constraint' => ['DNI', 'CE', 'PASAPORTE'],
                'default'    => 'DNI',
            ],
            'numero_documento' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'nombres' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'apellidos' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'correo' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'telefono' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'estado' => [
                'type'       => 'ENUM',
                'constraint' => ['ACTIVO', 'INACTIVO', 'BLOQUEADO'],
                'default'    => 'ACTIVO',
            ],
            'intentos_fallidos' => [
                'type'       => 'INT',
                'constraint' => 2,
                'default'    => 0,
            ],
            'bloqueado_hasta' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'ultimo_acceso' => [
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
        $this->forge->addUniqueKey('numero_documento');
        $this->forge->addUniqueKey('correo');

        $this->forge->addForeignKey(
            'rol_id',
            'roles',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->forge->createTable('usuarios');
    }
    public function down()
    {
        $this->forge->dropTable('usuarios');
    }
}