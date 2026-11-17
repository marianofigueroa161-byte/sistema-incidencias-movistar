<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\NotificacionModel;

class NotificacionesController extends BaseController
{
    public function index()
    {
        $usuarioId = (int) session()->get('usuario_id');
        $modelo = new NotificacionModel();

        return $this->response->setJSON([
            'success' => true,
            'notificaciones' => $modelo->listarPorUsuario($usuarioId),
            'no_leidas' => $modelo->contarNoLeidas($usuarioId),
        ]);
    }

    public function marcarLeida($id = null)
    {
        $usuarioId = (int) session()->get('usuario_id');
        $notificacionId = (int) $id;
        $modelo = new NotificacionModel();

        $notificacion = $modelo
            ->where('id', $notificacionId)
            ->where('usuario_id', $usuarioId)
            ->first();

        if (!$notificacion) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'La notificacion no existe.',
            ]);
        }

        if (!$modelo->marcarLeida($notificacionId, $usuarioId)) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No se pudo actualizar la notificacion.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'no_leidas' => $modelo->contarNoLeidas($usuarioId),
        ]);
    }

    public function marcarTodasLeidas()
    {
        $usuarioId = (int) session()->get('usuario_id');
        $modelo = new NotificacionModel();

        $resultado = $modelo->builder()
            ->where('usuario_id', $usuarioId)
            ->where('leida_at', null)
            ->update([
                'leida_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$resultado) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No se pudieron actualizar las notificaciones.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'no_leidas' => $modelo->contarNoLeidas($usuarioId),
        ]);
    }
}