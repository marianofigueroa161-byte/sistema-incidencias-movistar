<?php
namespace App\Models;
use CodeIgniter\Model;
class HistorialIncidenciaModel extends Model
{
    protected $table            = 'historial_incidencias';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields = [
        'incidencia_id',
        'usuario_id',
        'estado_anterior',
        'estado_nuevo',
        'comentario',
        'created_at',
    ];
    protected $useTimestamps = false;
    public function obtenerPorIncidencia(
        int $incidenciaId
    ): array {
        return $this
            ->select(
                'historial_incidencias.*, ' .
                'usuarios.nombres, usuarios.apellidos'
            )
            ->join(
                'usuarios',
                'usuarios.id = historial_incidencias.usuario_id',
                'left'
            )
            ->where(
                'historial_incidencias.incidencia_id',
                $incidenciaId
            )
            ->orderBy(
                'historial_incidencias.created_at',
                'ASC'
            )
            ->findAll();
    }
}