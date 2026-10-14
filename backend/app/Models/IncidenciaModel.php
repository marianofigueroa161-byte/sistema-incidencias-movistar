<?php
namespace App\Models;
use CodeIgniter\Model;
class IncidenciaModel extends Model
{
    protected $table            = 'incidencias';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields = [
        'codigo',
        'cliente_id',
        'servicio_id',
        'categoria_id',
        'tecnico_id',
        'asunto',
        'descripcion',
        'evidencia',
        'prioridad',
        'estado',
        'diagnostico',
        'solucion',
        'fecha_asignacion',
        'fecha_inicio',
        'fecha_solucion',
        'fecha_cierre',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    public function obtenerListado(): array
    {
        return $this
            ->select(
                'incidencias.*, ' .
                'categorias_incidencia.nombre AS categoria, ' .
                'servicios.codigo_servicio, ' .
                'clientes.nombres AS cliente_nombres, ' .
                'clientes.apellidos AS cliente_apellidos, ' .
                'tecnicos.nombres AS tecnico_nombres, ' .
                'tecnicos.apellidos AS tecnico_apellidos'
            )
            ->join(
                'categorias_incidencia',
                'categorias_incidencia.id = incidencias.categoria_id'
            )
            ->join(
                'servicios',
                'servicios.id = incidencias.servicio_id'
            )
            ->join(
                'usuarios AS clientes',
                'clientes.id = incidencias.cliente_id'
            )
            ->join(
                'usuarios AS tecnicos',
                'tecnicos.id = incidencias.tecnico_id',
                'left'
            )
            ->orderBy('incidencias.created_at', 'DESC')
            ->findAll();
    }
    public function obtenerResumen(): array
    {
        $resultado = $this->db
            ->table('incidencias')
            ->select(
                "
                COUNT(*) AS total,
                COALESCE(
                    SUM(
                        estado IN ('REGISTRADO', 'ASIGNADO')
                    ),
                    0
                ) AS pendientes,
                COALESCE(
                    SUM(estado = 'EN_PROCESO'),
                    0
                ) AS en_proceso,
                COALESCE(
                    SUM(
                        estado IN ('SOLUCIONADO', 'CERRADO')
                    ),
                    0
                ) AS solucionadas
                ",
                false
            )
            ->where('deleted_at', null)
            ->get()
            ->getRowArray();

        return [
            'total'        => (int) ($resultado['total'] ?? 0),
            'pendientes'   => (int) ($resultado['pendientes'] ?? 0),
            'en_proceso'   => (int) ($resultado['en_proceso'] ?? 0),
            'solucionadas' => (int) ($resultado['solucionadas'] ?? 0),
        ];
    }
}