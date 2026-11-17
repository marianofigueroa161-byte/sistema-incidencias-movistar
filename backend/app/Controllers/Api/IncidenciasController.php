<?php
namespace App\Controllers\Api;
use App\Controllers\BaseController;
use App\Models\CategoriaIncidenciaModel;
use App\Models\HistorialIncidenciaModel;
use App\Models\IncidenciaModel;
use App\Models\ServicioModel;
class IncidenciasController extends BaseController
{
    private function ejecutarTransaccion(string $mensaje, callable $operacion)
    {
        $baseDatos = db_connect();
        $iniciada = false;
        try {
            if (!$baseDatos->transBegin()) {
                throw new \RuntimeException('No se pudo iniciar la transacción.');
            }
            $iniciada = true;
            $respuesta = $operacion($baseDatos);
            if ($respuesta->getStatusCode() >= 400) {
                $this->verificarEscritura($baseDatos->transRollback());
                $iniciada = false;
                return $respuesta;
            }
            $this->verificarEscritura($baseDatos->transStatus());
            $this->verificarEscritura($baseDatos->transCommit());
            $iniciada = false;
            return $respuesta;
        } catch (\Throwable $error) {
            if ($iniciada) {
                try {
                    $baseDatos->transRollback();
                } catch (\Throwable $rollbackError) {
                    log_message('error', 'Rollback de incidencias: ' . $rollbackError->getMessage());
                }
            }
            log_message('error', 'Transacción de incidencias: ' . $error->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $mensaje,
            ]);
        }
    }

    private function verificarEscritura($resultado): void
    {
        if (!$resultado) {
            throw new \RuntimeException('No se completó la operación de base de datos.');
        }
    }

    private function notificar($baseDatos, int $usuarioId, int $incidenciaId, string $codigo, string $estado, string $titulo, string $mensaje): bool
    {
        return $baseDatos->table('notificaciones')->insert([
            'usuario_id' => $usuarioId,
            'incidencia_id' => $incidenciaId,
            'titulo' => $titulo,
            'mensaje' => $codigo . ': ' . $mensaje,
            'estado_nuevo' => $estado,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

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
    public function categorias()
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $modelo = new CategoriaIncidenciaModel();
        return $this->response->setJSON([
            'success' => true,
            'categorias' => $modelo->obtenerActivas(),
        ]);
    }
    public function servicios()
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        if (session()->get('rol') === 'TECNICO') {
            return $this->response->setJSON([
                'success' => true,
                'servicios' => [],
            ]);
        }

        $modelo = new ServicioModel();
        return $this->response->setJSON([
            'success' => true,
            'servicios' => session()->get('rol') === 'CLIENTE' ? $modelo->obtenerPorCliente((int) session()->get('usuario_id')) : $modelo->obtenerActivosConCliente(),
        ]);
    }
    public function index()
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $modelo = new IncidenciaModel();
        return $this->response->setJSON([
            'success' => true,
            'incidencias' => array_values(array_filter($modelo->obtenerListado(), static function ($fila) {
                $rol = session()->get('rol');
                $id = (int) session()->get('usuario_id');
                return $rol !== 'CLIENTE' && $rol !== 'TECNICO' || ($rol === 'CLIENTE' && (int) $fila['cliente_id'] === $id) || ($rol === 'TECNICO' && (int) $fila['tecnico_id'] === $id);
            })),
        ]);
    }
    public function resumen()
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $modelo = new IncidenciaModel();
        $rol = session()->get('rol');
        $resumen = $modelo->obtenerResumen();
        if ($rol === 'CLIENTE' || $rol === 'TECNICO') {
            $id = (int) session()->get('usuario_id');
            $registros = array_filter($modelo->obtenerListado(), static function ($fila) use ($rol, $id) {
                return (int) $fila[$rol === 'CLIENTE' ? 'cliente_id' : 'tecnico_id'] === $id;
            });
            $resumen = ['total' => count($registros), 'pendientes' => 0, 'en_proceso' => 0, 'solucionadas' => 0];
            foreach ($registros as $fila) {
                if (in_array($fila['estado'], ['REGISTRADO', 'ASIGNADO'], true)) $resumen['pendientes']++;
                if ($fila['estado'] === 'EN_PROCESO') $resumen['en_proceso']++;
                if (in_array($fila['estado'], ['SOLUCIONADO', 'CERRADO'], true)) $resumen['solucionadas']++;
            }
        }
        return $this->response->setJSON([
            'success' => true,
            'resumen' => $resumen,
        ]);
    }
    public function crear()
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $datos = $this->request->getJSON(true);
        if (!is_array($datos)) $datos = $this->request->getPost();
        $servicioId = (int) ($datos['servicio_id'] ?? 0);
        $categoriaId = (int) ($datos['categoria_id'] ?? 0);
        $asunto = trim($datos['asunto'] ?? '');
        $descripcion = trim($datos['descripcion'] ?? '');
        $prioridad = strtoupper(trim($datos['prioridad'] ?? ''));
        if ($servicioId <= 0 || $categoriaId <= 0 || $asunto === '' || $descripcion === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Complete todos los campos obligatorios.',
            ]);
        }
        $prioridadesPermitidas = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
        if (!in_array($prioridad, $prioridadesPermitidas, true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'La prioridad seleccionada no es válida.',
            ]);
        }
        return $this->ejecutarTransaccion('No se pudo registrar la incidencia.', function ($baseDatos) use ($servicioId, $categoriaId, $asunto, $descripcion, $prioridad) {
            $servicioModel = new ServicioModel($baseDatos);
            $servicio = $servicioModel->where('id', $servicioId)->where('estado', 'ACTIVO')->first();
            if (!$servicio) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'El servicio seleccionado no existe.',
                ]);
            }
            $categoriaModel = new CategoriaIncidenciaModel($baseDatos);
            if (session()->get('rol') === 'CLIENTE' && (int) $servicio['cliente_id'] !== (int) session()->get('usuario_id')) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'El servicio no pertenece a su cuenta.']);
            }
            $categoria = $categoriaModel->where('id', $categoriaId)->where('estado', 'ACTIVO')->first();
            if (!$categoria) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'La categoría seleccionada no existe.',
                ]);
            }
            $incidenciaModel = new IncidenciaModel($baseDatos);
            $historialModel = new HistorialIncidenciaModel($baseDatos);
            do {
                $codigo = 'INC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $codigoExiste = $incidenciaModel->where('codigo', $codigo)->first();
            } while ($codigoExiste);
            $incidenciaId = $incidenciaModel->insert([
                'codigo' => $codigo,
                'cliente_id' => $servicio['cliente_id'],
                'servicio_id' => $servicioId,
                'categoria_id' => $categoriaId,
                'asunto' => $asunto,
                'descripcion' => $descripcion,
                'prioridad' => $prioridad,
                'estado' => 'REGISTRADO',
            ], true);
            $this->verificarEscritura($incidenciaId);

            $this->verificarEscritura($historialModel->insert([
                'incidencia_id' => $incidenciaId,
                'usuario_id' => session()->get('usuario_id'),
                'estado_anterior' => null,
                'estado_nuevo' => 'REGISTRADO',
                'comentario' => 'Incidencia registrada.',
                'created_at' => date('Y-m-d H:i:s'),
            ]));

            $this->verificarEscritura($this->notificar(
                $baseDatos,
                (int) $servicio['cliente_id'],
                (int) $incidenciaId,
                $codigo,
                'REGISTRADO',
                'Incidencia registrada',
                'Su incidencia fue registrada correctamente.'
            ));

            return $this->response->setStatusCode(201)->setJSON([
                'success' => true,
                'message' => 'Incidencia registrada correctamente.',
                'data' => [
                    'id' => $incidenciaId,
                    'codigo' => $codigo,
                ],
            ]);
        });
    }
    public function asignarTecnico($id = null)
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $datos = $this->request->getJSON(true);
        if (!is_array($datos)) $datos = $this->request->getPost();
        $incidenciaId = (int) $id;
        $tecnicoId = (int) ($datos['tecnico_id'] ?? 0);
        $comentario = trim($datos['comentario'] ?? '');
        if ($incidenciaId <= 0 || $tecnicoId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Seleccione un técnico válido.',
            ]);
        }
        return $this->ejecutarTransaccion('No se pudo asignar el técnico.', function ($baseDatos) use ($incidenciaId, $tecnicoId, $comentario) {
            $incidenciaModel = new IncidenciaModel($baseDatos);
            $incidencia = $baseDatos->query('SELECT * FROM incidencias WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$incidenciaId])->getRowArray();
            if (!$incidencia) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'La incidencia no existe.',
                ]);
            }
            if (in_array($incidencia['estado'], ['SOLUCIONADO', 'CERRADO', 'CANCELADO'], true)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'No se puede asignar un técnico a esta incidencia.',
                ]);
            }
            if (!empty($incidencia['tecnico_id'])) {
                if ((int) $incidencia['tecnico_id'] === $tecnicoId) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'Seleccione un técnico diferente.',
                    ]);
                }

                if ($comentario === '') {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => 'Ingrese el motivo de la reasignación.',
                    ]);
                }
            }

            if ($comentario === '') {
                $comentario = 'Técnico asignado a la incidencia.';
            }

            $tecnico = $baseDatos->table('usuarios')
                ->select('usuarios.id')
                ->join('roles', 'roles.id = usuarios.rol_id')
                ->where('usuarios.id', $tecnicoId)
                ->where('usuarios.estado', 'ACTIVO')
                ->where('usuarios.deleted_at', null)
                ->where('roles.nombre', 'TECNICO')
                ->where('roles.estado', 'ACTIVO')
                ->get()
                ->getRowArray();

            if (!$tecnico) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'El técnico seleccionado no existe o está inactivo.',
                ]);
            }
            $estadoAnterior = $incidencia['estado'];
            $this->verificarEscritura($incidenciaModel->update($incidenciaId, [
                'tecnico_id' => $tecnicoId,
                'estado' => 'ASIGNADO',
                'fecha_asignacion' => date('Y-m-d H:i:s'),
            ]));
            $this->verificarEscritura((new HistorialIncidenciaModel($baseDatos))->insert([
                'incidencia_id' => $incidenciaId,
                'usuario_id' => session()->get('usuario_id'),
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'ASIGNADO',
                'comentario' => $comentario,
                'created_at' => date('Y-m-d H:i:s'),
            ]));

            $reasignacion = !empty($incidencia['tecnico_id'])
                && (int) $incidencia['tecnico_id'] !== $tecnicoId;

            $this->verificarEscritura($this->notificar(
                $baseDatos,
                (int) $incidencia['cliente_id'],
                $incidenciaId,
                $incidencia['codigo'],
                'ASIGNADO',
                $reasignacion ? 'Cambio de técnico' : 'Técnico asignado',
                $reasignacion
                    ? 'Se ha reasignado su incidencia a otro técnico.'
                    : 'Se ha asignado un técnico para atender su incidencia.'
            ));

            $this->verificarEscritura($this->notificar(
                $baseDatos,
                $tecnicoId,
                $incidenciaId,
                $incidencia['codigo'],
                'ASIGNADO',
                'Incidencia asignada',
                'Se le ha asignado una incidencia para su atención.'
            ));

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Técnico asignado correctamente.',
            ]);
        });
    }
    public function actualizarEstado($id = null)
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $datos = $this->request->getJSON(true);
        if (!is_array($datos)) $datos = $this->request->getPost();
        $incidenciaId = (int) $id;
        $estadoNuevo = strtoupper(trim($datos['estado'] ?? ''));
        $diagnostico = trim($datos['diagnostico'] ?? '');
        $solucion = trim($datos['solucion'] ?? '');
        $comentario = trim($datos['comentario'] ?? '');
        $estadosPermitidos = ['REGISTRADO', 'ASIGNADO', 'EN_PROCESO', 'SOLUCIONADO', 'CERRADO', 'CANCELADO'];
        if (!in_array($estadoNuevo, $estadosPermitidos, true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'El estado seleccionado no es válido.',
            ]);
        }
        return $this->ejecutarTransaccion('No se pudo actualizar la incidencia.', function ($baseDatos) use ($incidenciaId, $estadoNuevo, $diagnostico, $solucion, $comentario) {
            $incidenciaModel = new IncidenciaModel($baseDatos);
            $incidencia = $baseDatos->query('SELECT * FROM incidencias WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$incidenciaId])->getRowArray();
            if (!$incidencia) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'La incidencia no existe.',
                ]);
            }
            if (session()->get('rol') === 'TECNICO' && (int) $incidencia['tecnico_id'] !== (int) session()->get('usuario_id')) {
                return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'La incidencia no está asignada a su cuenta.']);
            }
            if (in_array($incidencia['estado'], ['CERRADO', 'CANCELADO'], true)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'La incidencia ya está finalizada.',
                ]);
            }
            if (in_array($estadoNuevo, ['ASIGNADO', 'EN_PROCESO', 'SOLUCIONADO'], true) && empty($incidencia['tecnico_id'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Primero debe asignar un técnico.',
                ]);
            }
            $transiciones = [
                'REGISTRADO' => ['CANCELADO'],
                'ASIGNADO' => ['EN_PROCESO', 'CANCELADO'],
                'EN_PROCESO' => ['EN_PROCESO', 'SOLUCIONADO', 'CANCELADO'],
                'SOLUCIONADO' => ['CERRADO'],
                'CERRADO' => [],
                'CANCELADO' => [],
            ];

            if (!in_array($estadoNuevo, $transiciones[$incidencia['estado']] ?? [], true)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'La transición de estado no está permitida.',
                ]);
            }

            if (session()->get('rol') === 'TECNICO' && !in_array($estadoNuevo, ['EN_PROCESO', 'SOLUCIONADO'], true)) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'No tiene permiso para realizar este cambio.',
                ]);
            }

            if (in_array($estadoNuevo, ['EN_PROCESO', 'SOLUCIONADO'], true)
                && session()->get('rol') !== 'TECNICO') {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Solo el técnico asignado puede actualizar la atención.',
                ]);
            }

            if (session()->get('rol') !== 'TECNICO' && ($diagnostico !== '' || $solucion !== '')) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Solo el técnico asignado puede modificar el diagnóstico y la solución.',
                ]);
            }

            $diagnosticoFinal = $diagnostico !== '' ? $diagnostico : trim((string) ($incidencia['diagnostico'] ?? ''));

            if ($estadoNuevo === 'SOLUCIONADO' && $diagnosticoFinal === '') {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Ingrese el diagnóstico antes de solucionar la incidencia.',
                ]);
            }

            if ($estadoNuevo === 'SOLUCIONADO' && $solucion === '') {
                return $this->response->setStatusCode(422)->setJSON([
                    'success' => false,
                    'message' => 'Ingrese la solución aplicada.',
                ]);
            }
            $actualizacion = ['estado' => $estadoNuevo];
            if ($diagnostico !== '') $actualizacion['diagnostico'] = $diagnostico;
            if ($solucion !== '') $actualizacion['solucion'] = $solucion;
            if ($estadoNuevo === 'EN_PROCESO' && empty($incidencia['fecha_inicio'])) $actualizacion['fecha_inicio'] = date('Y-m-d H:i:s');
            if ($estadoNuevo === 'SOLUCIONADO') $actualizacion['fecha_solucion'] = date('Y-m-d H:i:s');
            if ($estadoNuevo === 'CERRADO') $actualizacion['fecha_cierre'] = date('Y-m-d H:i:s');
            $this->verificarEscritura($incidenciaModel->update($incidenciaId, $actualizacion));
            $this->verificarEscritura((new HistorialIncidenciaModel($baseDatos))->insert([
                'incidencia_id' => $incidenciaId,
                'usuario_id' => session()->get('usuario_id'),
                'estado_anterior' => $incidencia['estado'],
                'estado_nuevo' => $estadoNuevo,
                'comentario' => $comentario !== '' ? $comentario : 'Estado actualizado a ' . $estadoNuevo . '.',
                'created_at' => date('Y-m-d H:i:s'),
            ]));

            if ($incidencia['estado'] !== $estadoNuevo) {
                $mensajes = [
                    'EN_PROCESO' => ['Incidencia en proceso', 'Su incidencia está siendo atendida.'],
                    'SOLUCIONADO' => ['Incidencia solucionada', 'Su incidencia ha sido solucionada.'],
                    'CERRADO' => ['Incidencia cerrada', 'La atención de su incidencia ha finalizado.'],
                    'CANCELADO' => ['Incidencia cancelada', 'Su incidencia ha sido cancelada.'],
                ];

                $notificacion = $mensajes[$estadoNuevo] ?? [
                    'Estado actualizado',
                    'El estado de su incidencia cambió a ' . $estadoNuevo . '.',
                ];

                $this->verificarEscritura($this->notificar(
                    $baseDatos,
                    (int) $incidencia['cliente_id'],
                    $incidenciaId,
                    $incidencia['codigo'],
                    $estadoNuevo,
                    $notificacion[0],
                    $notificacion[1]
                ));
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
            ]);
        });
    }
    public function historial($id = null)
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $incidenciaId = (int) $id;
        $incidencia = (new IncidenciaModel())->find($incidenciaId);
        if (!$incidencia) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'La incidencia no existe.',
            ]);
        }
        $rol = session()->get('rol');
        $idUsuario = (int) session()->get('usuario_id');
        if (($rol === 'CLIENTE' && (int) $incidencia['cliente_id'] !== $idUsuario) || ($rol === 'TECNICO' && (int) $incidencia['tecnico_id'] !== $idUsuario)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No tiene acceso a esta incidencia.']);
        }
        return $this->response->setJSON([
            'success' => true,
            'historial' => (new HistorialIncidenciaModel())->obtenerPorIncidencia($incidenciaId),
        ]);
    }
}