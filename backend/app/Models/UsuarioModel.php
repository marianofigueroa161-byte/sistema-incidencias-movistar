<?php
namespace App\Models;
use CodeIgniter\Model;
class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields = [
        'rol_id',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'correo',
        'telefono',
        'password_hash',
        'estado',
        'intentos_fallidos',
        'bloqueado_hasta',
        'ultimo_acceso',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $validationRules = [
        'rol_id'           => 'required|is_natural_no_zero',
        'numero_documento' => 'required|max_length[20]',
        'nombres'          => 'required|max_length[100]',
        'apellidos'        => 'required|max_length[100]',
        'correo'           => 'required|valid_email|max_length[150]',
        'password_hash'    => 'required|max_length[255]',
    ];
    public function buscarParaLogin(string $identificador): ?array
    {
        return $this
            ->select(
                'usuarios.*, roles.nombre AS rol_nombre'
            )
            ->join(
                'roles',
                'roles.id = usuarios.rol_id'
            )
            ->groupStart()
                ->where('usuarios.correo', $identificador)
                ->orWhere('usuarios.numero_documento', $identificador)
            ->groupEnd()
            ->first();
    }
}