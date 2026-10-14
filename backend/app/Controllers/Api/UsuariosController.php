<?php
namespace App\Controllers\Api;
use App\Controllers\BaseController;
use App\Models\ServicioModel;
use App\Models\UsuarioModel;
class UsuariosController extends BaseController
{
    private function sesionValida(): bool
    {
        return (bool) session()->get('autenticado');
    }
    private function respuestaSinSesion()
    {
        return $this->response
            ->setStatusCode(401)
            ->setJSON([
                'success' => false,
                'message' => 'La sesión ha finalizado.',
            ]);
    }
    public function clientes()
    {
        if (!$this->sesionValida()) {
            return $this->respuestaSinSesion();
        }
        $baseDatos = db_connect();
        $clientes = $baseDatos
            ->table('usuarios')
            ->select(
                'usuarios.id, usuarios.tipo_documento, ' .
                'usuarios.numero_documento, usuarios.nombres, ' .
                'usuarios.apellidos, usuarios.correo, ' .
                'usuarios.telefono, usuarios.estado, ' .
                'servicios.codigo_servicio, ' .
                'servicios.nombre_plan, ' .
                'servicios.velocidad_mbps, ' .
                'servicios.direccion_instalacion, ' .
                'servicios.distrito'
            )
            ->join(
                'roles',
                'roles.id = usuarios.rol_id'
            )
            ->join(
                'servicios',
                'servicios.cliente_id = usuarios.id ' .
                'AND servicios.deleted_at IS NULL',
                'left'
            )
            ->where('roles.nombre', 'CLIENTE')
            ->where('usuarios.deleted_at', null)
            ->orderBy('usuarios.created_at', 'DESC')
            ->get()
            ->getResultArray();
        return $this->response->setJSON([
            'success'  => true,
            'clientes' => $clientes,
        ]);
    }
    public function crearCliente()
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
            'codigo_servicio',
            'nombre_plan',
            'velocidad_mbps',
            'direccion_instalacion',
            'distrito',
        ];
        foreach ($camposObligatorios as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) === '') {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'success' => false,
                        'message' => 'Complete todos los campos obligatorios.',
                    ]);
            }
        }
        $tipoDocumento = strtoupper(
            trim($datos['tipo_documento'])
        );
        if (!in_array(
            $tipoDocumento,
            ['DNI', 'CE', 'PASAPORTE'],
            true
        )) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'El tipo de documento no es válido.',
                ]);
        }
        $correo = strtolower(trim($datos['correo']));
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'Ingrese un correo electrónico válido.',
                ]);
        }
        if (strlen($datos['clave']) < 8) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 8 caracteres.',
                ]);
        }
        $usuarioModel = new UsuarioModel();
        $usuarioExistente = $usuarioModel
            ->groupStart()
                ->where(
                    'numero_documento',
                    trim($datos['numero_documento'])
                )
                ->orWhere('correo', $correo)
            ->groupEnd()
            ->first();
        if ($usuarioExistente) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'success' => false,
                    'message' => 'El documento o correo ya está registrado.',
                ]);
        }
        $servicioModel = new ServicioModel();
        $servicioExistente = $servicioModel
            ->where(
                'codigo_servicio',
                trim($datos['codigo_servicio'])
            )
            ->first();
        if ($servicioExistente) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'success' => false,
                    'message' => 'El código del servicio ya está registrado.',
                ]);
        }
        $baseDatos = db_connect();
        $rolCliente = $baseDatos
            ->table('roles')
            ->where('nombre', 'CLIENTE')
            ->where('estado', 'ACTIVO')
            ->get()
            ->getRowArray();
        if (!$rolCliente) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se encontró el rol Cliente.',
                ]);
        }
        $baseDatos->transStart();
        $clienteId = $usuarioModel->insert([
            'rol_id'           => $rolCliente['id'],
            'tipo_documento'   => $tipoDocumento,
            'numero_documento' => trim(
                $datos['numero_documento']
            ),
            'nombres'          => trim($datos['nombres']),
            'apellidos'        => trim($datos['apellidos']),
            'correo'           => $correo,
            'telefono'         => trim(
                $datos['telefono'] ?? ''
            ) ?: null,
            'password_hash'    => password_hash(
                $datos['clave'],
                PASSWORD_DEFAULT
            ),
            'estado'           => 'ACTIVO',
            'intentos_fallidos'=> 0,
        ], true);

        $servicioModel->insert([
            'cliente_id'           => $clienteId,
            'codigo_servicio'      => trim(
                $datos['codigo_servicio']
            ),
            'tipo_servicio'        => 'INTERNET_FIJO',
            'nombre_plan'          => trim(
                $datos['nombre_plan']
            ),
            'velocidad_mbps'       => (int) $datos[
                'velocidad_mbps'
            ],
            'direccion_instalacion'=> trim(
                $datos['direccion_instalacion']
            ),
            'distrito'             => trim(
                $datos['distrito']
            ),
            'estado'               => 'ACTIVO',
        ]);
        $baseDatos->transComplete();
        if (!$baseDatos->transStatus()) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'success' => false,
                    'message' => 'No se pudo registrar el cliente.',
                ]);
        }
        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'success' => true,
                'message' => 'Cliente y servicio registrados correctamente.',
                'data'    => [
                    'cliente_id' => $clienteId,
                ],
            ]);
    }
}