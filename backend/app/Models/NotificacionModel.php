<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificacionModel extends Model
{
    protected $table = 'notificaciones';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'usuario_id',
        'incidencia_id',
        'titulo',
        'mensaje',
        'estado_nuevo',
        'leida_at',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'usuario_id' => 'required|is_natural_no_zero',
        'incidencia_id' => 'required|is_natural_no_zero',
        'titulo' => 'required|max_length[150]',
        'mensaje' => 'required',
        'estado_nuevo' => 'required|max_length[30]',
        'created_at' => 'required|valid_date[Y-m-d H:i:s]',
    ];

    public function listarPorUsuario(int $usuarioId): array
    {
        return $this->select('notificaciones.*, incidencias.codigo')
            ->join('incidencias', 'incidencias.id = notificaciones.incidencia_id')
            ->where('notificaciones.usuario_id', $usuarioId)
            ->orderBy('notificaciones.created_at', 'DESC')
            ->orderBy('notificaciones.id', 'DESC')
            ->findAll();
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        return $this->where('usuario_id', $usuarioId)
            ->where('leida_at', null)
            ->countAllResults();
    }

    public function marcarLeida(int $id, int $usuarioId): bool
    {
        return $this->builder()
            ->where('id', $id)
            ->where('usuario_id', $usuarioId)
            ->where('leida_at', null)
            ->update([
                'leida_at' => date('Y-m-d H:i:s'),
            ]);
    }
}