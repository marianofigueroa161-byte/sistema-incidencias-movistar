<?php
namespace App\Controllers\Api;
use App\Controllers\BaseController;
use App\Models\CategoriaIncidenciaModel;
use App\Models\HistorialIncidenciaModel;
use App\Models\IncidenciaModel;
use App\Models\ServicioModel;
class IncidenciasController extends BaseController
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
        $modelo = new ServicioModel();
        return $this->response->setJSON([
            'success' => true,
            'servicios' => $modelo->obtenerActivosConCliente(),
        ]);
    }
    public function index()
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $modelo = new IncidenciaModel();
        return $this->response->setJSON([
            'success' => true,
            'incidencias' => $modelo->obtenerListado(),
        ]);
    }
    public function resumen()
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $modelo = new IncidenciaModel();
        return $this->response->setJSON([
            'success' => true,
            'resumen' => $modelo->obtenerResumen(),
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
        $servicioModel = new ServicioModel();
        $servicio = $servicioModel->where('id', $servicioId)->where('estado', 'ACTIVO')->first();
        if (!$servicio) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'El servicio seleccionado no existe.',
            ]);
        }
        $categoriaModel = new CategoriaIncidenciaModel();
        $categoria = $categoriaModel->where('id', $categoriaId)->where('estado', 'ACTIVO')->first();
        if (!$categoria) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'La categoría seleccionada no existe.',
            ]);
        }
        $incidenciaModel = new IncidenciaModel();
        $historialModel = new HistorialIncidenciaModel();
        do {
            $codigo = 'INC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $codigoExiste = $incidenciaModel->where('codigo', $codigo)->first();
        } while ($codigoExiste);
        $baseDatos = db_connect();
        $baseDatos->transStart();
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
        $historialModel->insert([
            'incidencia_id' => $incidenciaId,
            'usuario_id' => session()->get('usuario_id'),
            'estado_anterior' => null,
            'estado_nuevo' => 'REGISTRADO',
            'comentario' => 'Incidencia registrada.',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $baseDatos->transComplete();
        if (!$baseDatos->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No se pudo registrar la incidencia.',
            ]);
        }
        return $this->response->setStatusCode(201)->setJSON([
            'success' => true,
            'message' => 'Incidencia registrada correctamente.',
            'data' => [
                'id' => $incidenciaId,
                'codigo' => $codigo,
            ],
        ]);
    }
    public function asignarTecnico($id = null)
    {
        if (!$this->sesionValida()) return $this->respuestaSinSesion();
        $datos = $this->request->getJSON(true);
        if (!is_array($datos)) $datos = $this->request->getPost();
        $incidenciaId = (int) $id;
        $tecnicoId = (int) ($datos['tecnico_id'] ?? 0);
        $comentario = trim($datos['comentario'] ?? 'Técnico asignado a la incidencia.');
        if ($incidenciaId <= 0 || $tecnicoId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Seleccione un técnico válido.',
            ]);
        }
        $incidenciaModel = new IncidenciaModel();
        $incidencia = $incidenciaModel->find($incidenciaId);
        if (!$incidencia) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'La incidencia no existe.',
            ]);
        }
        if (in_array($incidencia['estado'], ['CERRADO', 'CANCELADO'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'No se puede asignar un técnico a esta incidencia.',
            ]);
        }
        $tecnico = db_connect()->table('usuarios')
            ->select('usuarios.id')
            ->join('roles', 'roles.id = usuarios.rol_id')
            ->where('usuarios.id', $tecnicoId)
            ->where('usuarios.estado', 'ACTIVO')
            ->where('usuarios.deleted_at', null)
            ->where('roles.nombre', 'TECNICO')
            ->get()
            ->getRowArray();

        if (!$tecnico) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'El técnico seleccionado no existe o está inactivo.',
            ]);
        }
        $estadoAnterior = $incidencia['estado'];
        $baseDatos = db_connect();
        $baseDatos->transStart();
        $incidenciaModel->update($incidenciaId, [
            'tecnico_id' => $tecnicoId,
            'estado' => 'ASIGNADO',
            'fecha_asignacion' => date('Y-m-d H:i:s'),
        ]);
        (new HistorialIncidenciaModel())->insert([
            'incidencia_id' => $incidenciaId,
            'usuario_id' => session()->get('usuario_id'),
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'ASIGNADO',
            'comentario' => $comentario,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $baseDatos->transComplete();
        if (!$baseDatos->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No se pudo asignar el técnico.',
            ]);
        }
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Técnico asignado correctamente.',
        ]);
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
        $incidenciaModel = new IncidenciaModel();
        $incidencia = $incidenciaModel->find($incidenciaId);
        if (!$incidencia) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'La incidencia no existe.',
            ]);
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
        if ($estadoNuevo === 'SOLUCIONADO' && $solucion === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Ingrese la solución aplicada.',
            ]);
        }
        $actualizacion = ['estado' => $estadoNuevo];
        if ($diagnostico !== '') $actualizacion['diagnostico'] = $diagnostico;
        if ($solucion !== '') $actualizacion['solucion'] = $solucion;
        if ($estadoNuevo === 'EN_PROCESO') $actualizacion['fecha_inicio'] = date('Y-m-d H:i:s');
        if ($estadoNuevo === 'SOLUCIONADO') $actualizacion['fecha_solucion'] = date('Y-m-d H:i:s');
        if ($estadoNuevo === 'CERRADO') $actualizacion['fecha_cierre'] = date('Y-m-d H:i:s');
        $baseDatos = db_connect();
        $baseDatos->transStart();
        $incidenciaModel->update($incidenciaId, $actualizacion);
        (new HistorialIncidenciaModel())->insert([
            'incidencia_id' => $incidenciaId,
            'usuario_id' => session()->get('usuario_id'),
            'estado_anterior' => $incidencia['estado'],
            'estado_nuevo' => $estadoNuevo,
            'comentario' => $comentario !== '' ? $comentario : 'Estado actualizado a ' . $estadoNuevo . '.',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $baseDatos->transComplete();
        if (!$baseDatos->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No se pudo actualizar la incidencia.',
            ]);
        }
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
        ]);
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
        return $this->response->setJSON([
            'success' => true,
            'historial' => (new HistorialIncidenciaModel())->obtenerPorIncidencia($incidenciaId),
        ]);
    }
}