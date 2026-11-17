<?php
namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use App\Models\UsuarioModel;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $id = (int) session()->get('usuario_id');
        if (!session()->get('autenticado') || $id <= 0) {
            return service('response')->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Inicie sesión para continuar.']);
        }
        $usuario = (new UsuarioModel())->select('usuarios.id, usuarios.estado, roles.nombre AS rol_nombre, roles.estado AS rol_estado')->join('roles', 'roles.id = usuarios.rol_id')->where('usuarios.id', $id)->first();
        if (!$usuario || $usuario['estado'] !== 'ACTIVO' || $usuario['rol_estado'] !== 'ACTIVO') {
            session()->destroy();
            return service('response')->setStatusCode(401)->setJSON(['success' => false, 'message' => 'La sesión ya no es válida.']);
        }
        session()->set('rol', $usuario['rol_nombre']);
        if ($arguments && !in_array($usuario['rol_nombre'], $arguments, true)) {
            return service('response')->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No tiene permiso para realizar esta operación.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
