<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
class CategoriasIncidenciaSeeder extends Seeder
{
    public function run()
    {
        $fecha = date('Y-m-d H:i:s');

        $categorias = [
            [
                'nombre'             => 'Sin conexión',
                'descripcion'        => 'Pérdida total del servicio de internet.',
                'prioridad_default'  => 'ALTA',
                'estado'             => 'ACTIVO',
                'created_at'         => $fecha,
                'updated_at'         => $fecha,
            ],
            [
                'nombre'             => 'Baja velocidad',
                'descripcion'        => 'Velocidad inferior a la contratada.',
                'prioridad_default'  => 'MEDIA',
                'estado'             => 'ACTIVO',
                'created_at'         => $fecha,
                'updated_at'         => $fecha,
            ],
            [
                'nombre'             => 'Conexión intermitente',
                'descripcion'        => 'Cortes frecuentes durante la navegación.',
                'prioridad_default'  => 'MEDIA',
                'estado'             => 'ACTIVO',
                'created_at'         => $fecha,
                'updated_at'         => $fecha,
            ],
            [
                'nombre'             => 'Falla del router',
                'descripcion'        => 'Problema relacionado con el router instalado.',
                'prioridad_default'  => 'ALTA',
                'estado'             => 'ACTIVO',
                'created_at'         => $fecha,
                'updated_at'         => $fecha,
            ],
            [
                'nombre'             => 'Problema de fibra óptica',
                'descripcion'        => 'Avería o daño relacionado con la fibra óptica.',
                'prioridad_default'  => 'CRITICA',
                'estado'             => 'ACTIVO',
                'created_at'         => $fecha,
                'updated_at'         => $fecha,
            ],
            [
                'nombre'             => 'Otro problema',
                'descripcion'        => 'Incidencia no incluida en las demás categorías.',
                'prioridad_default'  => 'BAJA',
                'estado'             => 'ACTIVO',
                'created_at'         => $fecha,
                'updated_at'         => $fecha,
            ],
        ];
        $this->db
            ->table('categorias_incidencia')
            ->insertBatch($categorias);
    }
}