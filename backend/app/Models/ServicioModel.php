<?php
namespace App\Models;
use CodeIgniter\Model;
class ServicioModel extends Model
{
    protected $table            = 'servicios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields = [
        'cliente_id',
        'codigo_servicio',
        'tipo_servicio',
        'nombre_plan',
        'velocidad_mbps',
        'direccion_instalacion',
        'distrito',
        'estado',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    public function obtenerPorCliente(int $clienteId): array
    {
        return $this
            ->where('cliente_id', $clienteId)
            ->where('estado', 'ACTIVO')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
    public function obtenerActivosConCliente(): array
    {
        return $this
            ->select(
                'servicios.*, usuarios.nombres, usuarios.apellidos, ' .
                'usuarios.numero_documento'
            )
            ->join(
                'usuarios',
                'usuarios.id = servicios.cliente_id'
            )
            ->where('servicios.estado', 'ACTIVO')
            ->orderBy('usuarios.apellidos', 'ASC')
            ->findAll();
    }
}