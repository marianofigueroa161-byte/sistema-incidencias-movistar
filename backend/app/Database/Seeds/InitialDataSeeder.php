<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
class InitialDataSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'nombre'      => 'ADMINISTRADOR',
                'descripcion' => 'Gestiona usuarios, roles y configuraciones.',
                'estado'      => 'ACTIVO',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'nombre'      => 'SUPERVISOR',
                'descripcion' => 'Asigna técnicos y controla la atención.',
                'estado'      => 'ACTIVO',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'nombre'      => 'TECNICO',
                'descripcion' => 'Diagnostica y soluciona incidencias.',
                'estado'      => 'ACTIVO',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'nombre'      => 'CLIENTE',
                'descripcion' => 'Registra y consulta sus incidencias.',
                'estado'      => 'ACTIVO',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];
        $this->db->table('roles')->insertBatch($roles);
        $rolAdministrador = $this->db
            ->table('roles')
            ->where('nombre', 'ADMINISTRADOR')
            ->get()
            ->getRow();
        $administrador = [
            'rol_id'            => $rolAdministrador->id,
            'tipo_documento'    => 'DNI',
            'numero_documento'  => '00000000',
            'nombres'           => 'Administrador',
            'apellidos'         => 'General',
            'correo'            => 'admin@movistar.local',
            'telefono'          => null,
            'password_hash'     => password_hash('Admin123*', PASSWORD_DEFAULT),
            'estado'            => 'ACTIVO',
            'intentos_fallidos' => 0,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
        $this->db->table('usuarios')->insert($administrador);
    }
}