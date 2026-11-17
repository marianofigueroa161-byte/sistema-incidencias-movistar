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
        $claveInicial = env('SEED_ADMIN_PASSWORD');
        if (!is_string($claveInicial) || strlen($claveInicial) < 12) {
            throw new \RuntimeException('Configure SEED_ADMIN_PASSWORD con al menos 12 caracteres antes de ejecutar el seeder.');
        }
        $administrador = [
            'rol_id'            => $rolAdministrador->id,
            'tipo_documento'    => 'DNI',
            'numero_documento'  => '00000000',
            'nombres'           => 'Administrador',
            'apellidos'         => 'General',
            'correo'            => 'admin@movistar.local',
            'telefono'          => null,
            'password_hash'     => password_hash($claveInicial, PASSWORD_DEFAULT),
            'estado'            => 'ACTIVO',
            'intentos_fallidos' => 0,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];
        $this->db->table('usuarios')->insert($administrador);
    }
}