<?php
namespace App\Controllers\Api;
use App\Controllers\BaseController;
use App\Models\UsuarioModel;
class TecnicosController extends BaseController
{
    private function sesionValida(): bool
    {
        return (bool) session()->get('autenticado');
    }
    private function respuestaSinSesion()
    {
        return $this->response->setStatusCode(401)->setJSON([
            'success' => false,
            'message' => 'La sesión ha finalizado.',
        ]);
    }
    public function index()
    {
        if (!$this->sesionValida()) {
            return $this->respuestaSinSesion();
        }
        $baseDatos = db_connect();
        $tecnicos = $baseDatos->table('usuarios')
            ->select(
                "usuarios.id, usuarios.tipo_documento, usuarios.numero_documento,
                usuarios.nombres, usuarios.apellidos, usuarios.correo,
                usuarios.telefono, usuarios.estado, usuarios.ultimo_acceso,
                usuarios.created_at,
                (
                    SELECT COUNT(*)
                    FROM incidencias
                    WHERE incidencias.tecnico_id = usuarios.id
                    AND incidencias.deleted_at IS NULL
                ) AS incidencias_asignadas",
                false
            )
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('roles.nombre', 'TECNICO')
            ->where('usuarios.deleted_at', null)
            ->orderBy('usuarios.created_at', 'DESC')
            ->get()
            ->getResultArray();
        return $this->response->setJSON([
            'success' => true,
            'tecnicos' => $tecnicos,
        ]);
    }
    public function crear()
    {
        if (!$this->sesionValida()) {
            return $this->respuestaSinSesion();
        }
        $datos = $this->request->getJSON(true);

        if (!is_array($datos)) {
            $datos = $this->request->getPost();
        }
        $camposObligatorios = [
            'tipo_documento',
            'numero_documento',
            'nombres',
            'apellidos',
            'correo',
            'clave',
        ];
        foreach ($camposObligatorios as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) === '') {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Complete todos los campos obligatorios.',
                ]);
            }
        }
        $tipoDocumento = strtoupper(trim($datos['tipo_documento']));
        $numeroDocumento = trim($datos['numero_documento']);
        $correo = strtolower(trim($datos['correo']));
        $clave = (string) $datos['clave'];
        if (!in_array($tipoDocumento, ['DNI', 'CE', 'PASAPORTE'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El tipo de documento no es válido.',
            ]);
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Ingrese un correo electrónico válido.',
            ]);
        }
        if (strlen($clave) < 8) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres.',
            ]);
        }
        $usuarioModel = new UsuarioModel();
        $usuarioExistente = $usuarioModel
            ->groupStart()
            ->where('numero_documento', $numeroDocumento)
            ->orWhere('correo', $correo)
            ->groupEnd()
            ->first();

        if ($usuarioExistente) {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => 'El documento o correo ya está registrado.',
            ]);
        }
        $baseDatos = db_connect();
        $rolTecnico = $baseDatos->table('roles')
            ->where('nombre', 'TECNICO')
            ->where('estado', 'ACTIVO')
            ->get()
            ->getRowArray();
        if (!$rolTecnico) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No se encontró el rol Técnico.',
            ]);
        }
        $tecnicoId = $usuarioModel->insert([
            'rol_id' => $rolTecnico['id'],
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'nombres' => trim($datos['nombres']),
            'apellidos' => trim($datos['apellidos']),
            'correo' => $correo,
            'telefono' => trim($datos['telefono'] ?? '') ?: null,
            'password_hash' => password_hash($clave, PASSWORD_DEFAULT),
            'estado' => 'ACTIVO',
            'intentos_fallidos' => 0,
        ], true);
        if (!$tecnicoId) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No se pudo registrar el técnico.',
            ]);
        }
        return $this->response->setStatusCode(201)->setJSON([
            'success' => true,
            'message' => 'Técnico registrado correctamente.',
            'data' => [
                'tecnico_id' => $tecnicoId,
            ],
        ]);
    }
}