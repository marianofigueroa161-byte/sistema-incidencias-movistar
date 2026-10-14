<?php
namespace App\Models;
use CodeIgniter\Model;
class CategoriaIncidenciaModel extends Model
{
    protected $table            = 'categorias_incidencia';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields = [
        'nombre',
        'descripcion',
        'prioridad_default',
        'estado',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    public function obtenerActivas(): array
    {
        return $this
            ->where('estado', 'ACTIVO')
            ->orderBy('nombre', 'ASC')
            ->findAll();
    }
}