<?php
namespace App\Controllers\Api;
use App\Controllers\BaseController;
use App\Models\UsuarioModel;
class AuthController extends BaseController
{
    public function login()
    {
        $datos = $this->request->getJSON(true);
        if (!is_array($datos)) {
            $datos = $this->request->getPost();
        }
        $identificador = trim($datos['usuario'] ?? '');
        $clave         = $datos['clave'] ?? '';
        if ($identificador === '' || $clave === '') {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'success' => false,
                    'message' => 'El usuario y la contraseña son obligatorios.',
                ]);
        }
        $usuarioModel = new UsuarioModel();
        $usuario      = $usuarioModel->buscarParaLogin($identificador);
        if (!$usuario) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Las credenciales ingresadas son incorrectas.',
                ]);
        }
        if ($usuario['estado'] === 'INACTIVO') {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'success' => false,
                    'message' => 'La cuenta se encuentra inactiva.',
                ]);
        }
        if (
            $usuario['estado'] === 'BLOQUEADO'
            && !empty($usuario['bloqueado_hasta'])
            && strtotime($usuario['bloqueado_hasta']) > time()
        ) {
            return $this->response
                ->setStatusCode(423)
                ->setJSON([
                    'success' => false,
                    'message' => 'La cuenta está bloqueada temporalmente. Intente más tarde.',
                ]);
        }
        if (!password_verify($clave, $usuario['password_hash'])) {
            $intentos = ((int) $usuario['intentos_fallidos']) + 1;
            $actualizacion = [
                'intentos_fallidos' => $intentos,
            ];
            if ($intentos >= 5) {
                $actualizacion['estado'] = 'BLOQUEADO';
                $actualizacion['bloqueado_hasta'] = date(
                    'Y-m-d H:i:s',
                    strtotime('+15 minutes')
                );
            }
            $usuarioModel->update($usuario['id'], $actualizacion);
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => $intentos >= 5
                        ? 'La cuenta fue bloqueada durante 15 minutos.'
                        : 'Las credenciales ingresadas son incorrectas.',
                ]);
        }
        $usuarioModel->update($usuario['id'], [
            'estado'            => 'ACTIVO',
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
            'ultimo_acceso'     => date('Y-m-d H:i:s'),
        ]);
        $datosSesion = [
            'usuario_id'     => $usuario['id'],
            'rol_id'         => $usuario['rol_id'],
            'rol'            => $usuario['rol_nombre'],
            'nombres'        => $usuario['nombres'],
            'apellidos'      => $usuario['apellidos'],
            'correo'         => $usuario['correo'],
            'autenticado'    => true,
        ];
        session()->set($datosSesion);
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'usuario' => [
                'id'        => $usuario['id'],
                'nombres'   => $usuario['nombres'],
                'apellidos' => $usuario['apellidos'],
                'correo'    => $usuario['correo'],
                'rol'       => $usuario['rol_nombre'],
            ],
        ]);
    }
    public function sesion()
    {
        if (!session()->get('autenticado')) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'No existe una sesión activa.',
                ]);
        }
        return $this->response->setJSON([
            'success' => true,
            'usuario' => [
                'id'        => session()->get('usuario_id'),
                'nombres'   => session()->get('nombres'),
                'apellidos' => session()->get('apellidos'),
                'correo'    => session()->get('correo'),
                'rol'       => session()->get('rol'),
            ],
        ]);
    }
    public function logout()
    {
        session()->destroy();
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}