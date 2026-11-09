<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
const router = useRouter()
const authStore = useAuthStore()
const incidencias = ref([])
const categorias = ref([])
const servicios = ref([])
const tecnicos = ref([])
const historial = ref([])
const incidenciaSeleccionada = ref(null)
const cargando = ref(true)
const guardando = ref(false)
const mostrarFormulario = ref(false)
const mostrarGestion = ref(false)
const busqueda = ref('')
const filtroEstado = ref('')
const filtroPrioridad = ref('')
const mensaje = ref('')
const tipoMensaje = ref('')
const formulario = reactive({
  servicio_id: '',
  categoria_id: '',
  asunto: '',
  descripcion: '',
  prioridad: 'MEDIA',
})
const gestion = reactive({ tecnico_id: '', estado: '', diagnostico: '', solucion: '', comentario: '' })
const incidenciasFiltradas = computed(() => {
  const texto = busqueda.value.toLowerCase().trim()
  return incidencias.value.filter((incidencia) => {
    const coincideTexto =
      texto === '' ||
      incidencia.codigo?.toLowerCase().includes(texto) ||
      incidencia.asunto?.toLowerCase().includes(texto) ||
      incidencia.cliente_nombres?.toLowerCase().includes(texto) ||
      incidencia.cliente_apellidos?.toLowerCase().includes(texto)
    const coincideEstado =
      filtroEstado.value === '' ||
      incidencia.estado === filtroEstado.value
    const coincidePrioridad =
      filtroPrioridad.value === '' ||
      incidencia.prioridad === filtroPrioridad.value
    return (
      coincideTexto &&
      coincideEstado &&
      coincidePrioridad
    )
  })
})
const cargarDatos = async () => {
  cargando.value = true
  mensaje.value = ''
  try {
    const [
      respuestaIncidencias,
      respuestaCategorias,
      respuestaServicios,
      respuestaTecnicos,
    ] = await Promise.all([
      api.get('/incidencias'),
      api.get('/categorias-incidencia'),
      api.get('/servicios'),
      api.get('/tecnicos'),
    ])
    incidencias.value =
      respuestaIncidencias.data.incidencias
    categorias.value =
      respuestaCategorias.data.categorias
    servicios.value =
      respuestaServicios.data.servicios
    tecnicos.value = respuestaTecnicos.data.tecnicos || []
  } catch (error) {
    if (error.response?.status === 401) {
      await authStore.cerrarSesion()
      router.push('/login')
      return
    }
    tipoMensaje.value = 'error'
    mensaje.value =
      error.response?.data?.message ||
      'No fue posible cargar las incidencias.'
  } finally {
    cargando.value = false
  }
}
const cambiarCategoria = () => {
  const categoria = categorias.value.find(
    (item) =>
      Number(item.id) ===
      Number(formulario.categoria_id)
  )
  if (categoria) {
    formulario.prioridad =
      categoria.prioridad_default
  }
}
const limpiarFormulario = () => {
  formulario.servicio_id = ''
  formulario.categoria_id = ''
  formulario.asunto = ''
  formulario.descripcion = ''
  formulario.prioridad = 'MEDIA'
}
const abrirFormulario = () => {
  mensaje.value = ''
  limpiarFormulario()
  mostrarFormulario.value = true
}
const cerrarFormulario = () => {
  if (!guardando.value) {
    mostrarFormulario.value = false
  }
}
const registrarIncidencia = async () => {
  mensaje.value = ''
  guardando.value = true
  try {
    const respuesta = await api.post(
      '/incidencias',
      formulario
    )
    tipoMensaje.value = 'success'
    mensaje.value =
      `${respuesta.data.message} Código: ` +
      respuesta.data.data.codigo
    mostrarFormulario.value = false
    await cargarDatos()
  } catch (error) {
    tipoMensaje.value = 'error'
    mensaje.value =
      error.response?.data?.message ||
      'No se pudo registrar la incidencia.'
  } finally {
    guardando.value = false
  }
}
const abrirGestion = async (incidencia) => {
  mensaje.value = ''
  incidenciaSeleccionada.value = incidencia
  gestion.tecnico_id = incidencia.tecnico_id || ''
  gestion.estado = incidencia.estado
  gestion.diagnostico = incidencia.diagnostico || ''
  gestion.solucion = incidencia.solucion || ''
  gestion.comentario = ''
  historial.value = []
  mostrarGestion.value = true
  try {
    const respuesta = await api.get(`/incidencias/${incidencia.id}/historial`)
    historial.value = respuesta.data.historial || []
  } catch (error) {
    tipoMensaje.value = 'error'
    mensaje.value = error.response?.data?.message || 'No se pudo cargar el historial.'
  }
}
const cerrarGestion = () => {
  if (!guardando.value) mostrarGestion.value = false
}
const asignarTecnico = async () => {
  if (!gestion.tecnico_id) {
    tipoMensaje.value = 'error'
    mensaje.value = 'Seleccione un técnico.'
    return
  }
  guardando.value = true
  mensaje.value = ''
  try {
    const respuesta = await api.put(`/incidencias/${incidenciaSeleccionada.value.id}/asignar`, {
      tecnico_id: gestion.tecnico_id,
      comentario: gestion.comentario,
    })
    tipoMensaje.value = 'success'
    mensaje.value = respuesta.data.message
    mostrarGestion.value = false
    await cargarDatos()
  } catch (error) {
    tipoMensaje.value = 'error'
    mensaje.value = error.response?.data?.message || 'No se pudo asignar el técnico.'
  } finally {
    guardando.value = false
  }
}
const actualizarEstado = async () => {
  guardando.value = true
  mensaje.value = ''
  try {
    const respuesta = await api.put(`/incidencias/${incidenciaSeleccionada.value.id}/estado`, {
      estado: gestion.estado,
      diagnostico: gestion.diagnostico,
      solucion: gestion.solucion,
      comentario: gestion.comentario,
    })
    tipoMensaje.value = 'success'
    mensaje.value = respuesta.data.message
    mostrarGestion.value = false
    await cargarDatos()
  } catch (error) {
    tipoMensaje.value = 'error'
    mensaje.value = error.response?.data?.message || 'No se pudo actualizar el estado.'
  } finally {
    guardando.value = false
  }
}
const formatearFecha = (fecha) => {
  if (!fecha) {
    return '-'
  }
  return new Intl.DateTimeFormat('es-PE', {
    dateStyle: 'short',
    timeStyle: 'short',
  }).format(new Date(fecha))
}
const claseEstado = (estado) => {
  return `estado estado-${estado.toLowerCase()}`
}
const clasePrioridad = (prioridad) => {
  return `prioridad prioridad-${prioridad.toLowerCase()}`
}
const cerrarSesion = async () => {
  await authStore.cerrarSesion()
  router.push('/login')
}
onMounted(cargarDatos)
</script>
<template>
  <div class="app-layout" translate="no">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-icon">M</div>
        <strong>Movistar</strong>
      </div>
      <nav class="menu">
        <button
          class="menu-item"
          @click="router.push('/dashboard')"
        >
          <i class="bi bi-grid-1x2-fill"></i>
          Panel principal
        </button>
        <button class="menu-item active">
          <i class="bi bi-ticket-perforated"></i>
          Incidencias
        </button>
        <button class="menu-item" type="button" @click="router.push('/usuarios')">
          <i class="bi bi-people"></i>
          Usuarios
        </button>
        <button class="menu-item" type="button" @click="router.push('/tecnicos')">
        <i class="bi bi-person-gear"></i>
         Técnicos
        </button>
        <button class="menu-item" type="button" @click="router.push('/reportes')">
        <i class="bi bi-bar-chart"></i>
         Reportes
        </button>
      </nav>
      <button
        class="logout-button"
        @click="cerrarSesion"
      >
        <i class="bi bi-box-arrow-left"></i>
        Cerrar sesión
      </button>
    </aside>
    <main class="content">

      <div
        v-if="mensaje"
        :class="['alerta', tipoMensaje]"
      >
        <i
          :class="
            tipoMensaje === 'success'
              ? 'bi bi-check-circle'
              : 'bi bi-exclamation-circle'
          "
        ></i>
        {{ mensaje }}
      </div>
      <section class="panel">
        <div class="panel-header">
          <div class="filters">
            <div class="search">
              <i class="bi bi-search"></i>
              <input
                v-model="busqueda"
                type="search"
                placeholder=""
              />
            </div>
            <select v-model="filtroEstado">
              <option value="" hidden></option>
              <option value="REGISTRADO">Registrado</option>
              <option value="ASIGNADO">Asignado</option>
              <option value="EN_PROCESO">En proceso</option>
              <option value="SOLUCIONADO">Solucionado</option>
              <option value="CERRADO">Cerrado</option>
            </select>
            <select v-model="filtroPrioridad">
              <option value="" hidden></option>
              <option value="BAJA">Baja</option>
              <option value="MEDIA">Media</option>
              <option value="ALTA">Alta</option>
              <option value="CRITICA">Crítica</option>
            </select>
          </div>
          <button
            class="primary-button"
            @click="abrirFormulario"
          >
            <i class="bi bi-plus-lg"></i>
            Nueva incidencia
          </button>
        </div>
        <div v-if="cargando" class="loading">
          <span class="spinner-border"></span>
          Cargando incidencias...
        </div>
        <div
          v-else-if="incidenciasFiltradas.length === 0"
          class="empty-state"
        >
          <i class="bi bi-inbox"></i>
          <strong>No existen incidencias</strong>
        </div>
        <div v-else class="table-container">
          <table>
            <thead>
              <tr>
                <th>Código</th>
                <th>Cliente</th>
                <th>Asunto</th>
                <th>Categoría</th>
                <th>Prioridad</th>
                <th>Estado</th>
                <th>Técnico</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="incidencia in incidenciasFiltradas"
                :key="incidencia.id"
              >
                <td>
                  <strong>{{ incidencia.codigo }}</strong>
                </td>
                <td>
                  {{ incidencia.cliente_nombres }}
                  {{ incidencia.cliente_apellidos }}
                </td>
                <td>{{ incidencia.asunto }}</td>
                <td>{{ incidencia.categoria }}</td>
                <td>
                  <span
                    :class="
                      clasePrioridad(incidencia.prioridad)
                    "
                  >
                    {{ incidencia.prioridad }}
                  </span>
                </td>
                <td>
                  <span
                    :class="claseEstado(incidencia.estado)"
                  >
                    {{
                      incidencia.estado.replace('_', ' ')
                    }}
                  </span>
                </td>
                <td>
                  {{ incidencia.tecnico_nombres ? `${incidencia.tecnico_nombres} ${incidencia.tecnico_apellidos}` : 'Sin asignar' }}
                </td>
                <td>
                  {{ formatearFecha(incidencia.created_at) }}
                </td>
                <td>
                  <button class="manage-button" type="button" @click="abrirGestion(incidencia)">
                    <i class="bi bi-pencil-square"></i>
                    Gestionar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
    <div
      v-if="mostrarFormulario"
      class="modal-backdrop"
      @click.self="cerrarFormulario"
    >
      <section class="modal-card">
        <header class="modal-header">
          <div>
            <h2>Nueva incidencia</h2>
            <p>Registra una solicitud de soporte técnico.</p>
          </div>
          <button
            type="button"
            :disabled="guardando"
            @click="cerrarFormulario"
          >
            <i class="bi bi-x-lg"></i>
          </button>
        </header>
        <form @submit.prevent="registrarIncidencia">
          <div class="form-group full">
            <label for="servicio">Cliente y servicio</label>
            <select
              id="servicio"
              v-model="formulario.servicio_id"
              required
            >
              <option value="" disabled>
                Seleccione un servicio
              </option>
              <option
                v-for="servicio in servicios"
                :key="servicio.id"
                :value="servicio.id"
              >
                {{ servicio.nombres }}
                {{ servicio.apellidos }} —
                {{ servicio.codigo_servicio }} —
                {{ servicio.nombre_plan }}
              </option>
            </select>
            <small v-if="servicios.length === 0">
              Primero debe registrar un cliente y su servicio.
            </small>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="categoria">Categoría</label>
              <select
                id="categoria"
                v-model="formulario.categoria_id"
                required
                @change="cambiarCategoria"
              >
                <option value="" disabled>
                  Seleccione una categoría
                </option>
                <option
                  v-for="categoria in categorias"
                  :key="categoria.id"
                  :value="categoria.id"
                >
                  {{ categoria.nombre }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label for="prioridad">Prioridad</label>
              <select
                id="prioridad"
                v-model="formulario.prioridad"
                required
              >
                <option value="BAJA">Baja</option>
                <option value="MEDIA">Media</option>
                <option value="ALTA">Alta</option>
                <option value="CRITICA">Crítica</option>
              </select>
            </div>
          </div>
          <div class="form-group full">
            <label for="asunto">Asunto</label>
            <input
              id="asunto"
              v-model.trim="formulario.asunto"
              type="text"
              maxlength="150"
              placeholder="Ejemplo: Pérdida total de conexión"
              required
            />
          </div>
          <div class="form-group full">
            <label for="descripcion">Descripción</label>
            <textarea
              id="descripcion"
              v-model.trim="formulario.descripcion"
              rows="5"
              placeholder="Describa el problema presentado"
              required
            ></textarea>
          </div>
          <footer class="modal-actions">
            <button
              class="secondary-button"
              type="button"
              :disabled="guardando"
              @click="cerrarFormulario"
            >
              Cancelar
            </button>
            <button
              class="primary-button"
              type="submit"
              :disabled="
                guardando || servicios.length === 0
              "
            >
              <span v-if="guardando">
                <span
                  class="spinner-border spinner-border-sm"
                ></span>
                Guardando...
              </span>
              <span v-else>
                Registrar incidencia
              </span>
            </button>
          </footer>
        </form>
      </section>
    </div>
    <div v-if="mostrarGestion" class="modal-backdrop" @click.self="cerrarGestion">
      <section class="modal-card">
        <header class="modal-header">
          <div>
            <h2>Gestionar incidencia</h2>
            <p>{{ incidenciaSeleccionada?.codigo }} - {{ incidenciaSeleccionada?.asunto }}</p>
          </div>
          <button type="button" :disabled="guardando" @click="cerrarGestion"><i class="bi bi-x-lg"></i></button>
        </header>
        <form @submit.prevent="actualizarEstado">
          <div class="form-row">
            <div class="form-group">
              <label for="tecnico">Técnico</label>
              <select id="tecnico" v-model="gestion.tecnico_id">
                <option value="" disabled>Seleccione un técnico</option>
                <option v-for="tecnico in tecnicos" :key="tecnico.id" :value="tecnico.id">{{ tecnico.nombres }} {{ tecnico.apellidos }}</option>
              </select>
            </div>
            <div class="form-group">
              <label for="estadoGestion">Estado</label>
              <select id="estadoGestion" v-model="gestion.estado">
                <option value="REGISTRADO">Registrado</option>
                <option value="ASIGNADO">Asignado</option>
                <option value="EN_PROCESO">En proceso</option>
                <option value="SOLUCIONADO">Solucionado</option>
                <option value="CERRADO">Cerrado</option>
              </select>
            </div>
          </div>
          <div class="form-group full">
            <label for="diagnostico">Diagnóstico</label>
            <textarea id="diagnostico" v-model.trim="gestion.diagnostico" rows="3" placeholder="Ingrese el diagnóstico técnico"></textarea>
          </div>
          <div class="form-group full">
            <label for="solucion">Solución</label>
            <textarea id="solucion" v-model.trim="gestion.solucion" rows="3" placeholder="Ingrese la solución aplicada"></textarea>
          </div>
          <div class="form-group full">
            <label for="comentario">Comentario</label>
            <textarea id="comentario" v-model.trim="gestion.comentario" rows="2" placeholder="Detalle adicional del cambio"></textarea>
          </div>
          <div class="history" v-if="historial.length">
            <strong>Historial</strong>
            <div v-for="item in historial" :key="item.id" class="history-item">
              <span>{{ item.estado_nuevo.replace('_', ' ') }}</span>
              <small>{{ item.comentario }} · {{ formatearFecha(item.created_at) }}</small>
            </div>
          </div>
          <footer class="modal-actions">
            <button class="secondary-button" type="button" :disabled="guardando" @click="cerrarGestion">Cancelar</button>
            <button class="secondary-button" type="button" :disabled="guardando || !gestion.tecnico_id" @click="asignarTecnico">Asignar técnico</button>
            <button class="primary-button" type="submit" :disabled="guardando">{{ guardando ? 'Guardando...' : 'Actualizar estado' }}</button>
          </footer>
        </form>
      </section>
    </div>
  </div>
</template>
<style scoped>
* {
  box-sizing: border-box;
}
.app-layout {
  display: grid;
  grid-template-columns: 250px 1fr;
  min-height: 100vh;
  color: #18324a;
  background: #f4f7fa;
  font-family: Arial, Helvetica, sans-serif;
}
.sidebar {
  position: sticky;
  top: 0;
  display: flex;
  height: 100vh;
  flex-direction: column;
  padding: 28px 18px;
  color: white;
  background: linear-gradient(180deg, #087ebc, #005d91);
}
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 12px 32px;
  font-size: 22px;
}
.brand-icon {
  display: grid;
  width: 42px;
  height: 42px;
  place-items: center;
  border-radius: 13px;
  color: #0783c2;
  background: white;
  font-size: 24px;
  font-weight: 800;
}
.menu {
  display: grid;
  gap: 7px;
}
.menu-item,
.logout-button {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 13px;
  padding: 13px 15px;
  border: 0;
  border-radius: 10px;
  color: rgba(255, 255, 255, 0.82);
  background: transparent;
  font-size: 14px;
  text-align: left;
  cursor: pointer;
}
.menu-item:hover,
.menu-item.active,
.logout-button:hover {
  color: white;
  background: rgba(255, 255, 255, 0.14);
}
.logout-button {
  margin-top: auto;
}
.content {
  min-width: 0;
  padding: 20px 24px;
}
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 26px;
}
.topbar h1 {
  margin: 0 0 5px;
  font-size: 28px;
}
.topbar p {
  margin: 0;
  color: #718295;
  font-size: 13px;
}
.user {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 14px;
}
.avatar {
  display: grid;
  width: 43px;
  height: 43px;
  place-items: center;
  border-radius: 50%;
  color: white;
  background: #019be1;
  font-weight: 800;
}
.alerta {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 18px;
  padding: 13px 16px;
  border-radius: 10px;
  font-size: 13px;
}
.alerta.success {
  color: #136b49;
  background: #e4f7ee;
}
.alerta.error {
  color: #9d3737;
  background: #fdeaea;
}
.panel {
  overflow: hidden;
  min-height: 0;
  border: 1px solid #e4ebf1;
  border-radius: 17px;
  background: white;
}
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 22px;
  border-bottom: 1px solid #edf1f4;
}
.filters {
  display: flex;
  flex: 1;
  gap: 12px;
}
.search {
  position: relative;
  flex: 1;
  max-width: 390px;
}
.search i {
  position: absolute;
  top: 50%;
  left: 14px;
  color: #8494a3;
  transform: translateY(-50%);
}
.search input,
.filters select,
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  border: 1px solid #d9e2ea;
  border-radius: 10px;
  outline: none;
  color: #20384d;
  background: white;
  font-size: 13px;
}
.search input {
  height: 43px;
  padding: 0 14px 0 41px;
}
.filters select {
  width: 180px;
  height: 43px;
  padding: 0 11px;
}
.search input:focus,
.filters select:focus,
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #019be1;
  box-shadow: 0 0 0 3px rgba(1, 155, 225, 0.1);
}
.primary-button,
.secondary-button {
  display: inline-flex;
  min-height: 43px;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 17px;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}
.primary-button {
  border: 0;
  color: white;
  background: #019be1;
}
.primary-button:hover:not(:disabled) {
  background: #0086c4;
}
.primary-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
.secondary-button {
  border: 1px solid #d8e1e9;
  color: #536577;
  background: white;
}
.manage-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  border: 1px solid #019be1;
  border-radius: 8px;
  color: #0077b3;
  background: white;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}
.manage-button:hover {
  color: white;
  background: #019be1;
}
.loading,
.empty-state {
  display: flex;
  min-height: 140px;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 10px;
  color: #8494a3;
}
.empty-state i {
  color: #b8c5cf;
  font-size: 44px;
}
.empty-state strong {
  color: #536577;
}
.empty-state span {
  font-size: 12px;
}
.table-container {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th,
td {
  padding: 15px 18px;
  border-bottom: 1px solid #edf1f4;
  font-size: 12px;
  text-align: left;
  white-space: nowrap;
}
th {
  color: #6b7e90;
  background: #f8fafc;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}
.estado,
.prioridad {
  display: inline-flex;
  padding: 5px 9px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 800;
}
.estado-registrado,
.estado-asignado {
  color: #946000;
  background: #fff1ce;
}
.estado-en_proceso {
  color: #6541aa;
  background: #eee7ff;
}
.estado-solucionado,
.estado-cerrado {
  color: #13704b;
  background: #ddf5e9;
}
.prioridad-baja {
  color: #466174;
  background: #eaf0f4;
}
.prioridad-media {
  color: #8a6400;
  background: #fff1c9;
}
.prioridad-alta {
  color: #aa5415;
  background: #ffeadb;
}
.prioridad-critica {
  color: #a02f3a;
  background: #fde1e4;
}
.modal-backdrop {
  position: fixed;
  z-index: 1000;
  inset: 0;
  display: grid;
  overflow-y: auto;
  padding: 30px 20px;
  background: rgba(15, 35, 52, 0.55);
  place-items: center;
}
.modal-card {
  width: min(100%, 680px);
  border-radius: 18px;
  background: white;
  box-shadow: 0 25px 80px rgba(13, 34, 51, 0.25);
}
.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 24px 26px;
  border-bottom: 1px solid #edf1f4;
}
.modal-header h2 {
  margin: 0 0 6px;
  font-size: 22px;
}
.modal-header p {
  margin: 0;
  color: #718295;
  font-size: 12px;
}
.modal-header > button {
  border: 0;
  color: #6b7e90;
  background: transparent;
  cursor: pointer;
}
.modal-card form {
  padding: 24px 26px;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.form-group {
  margin-bottom: 18px;
}
.form-group label {
  display: block;
  margin-bottom: 7px;
  color: #31495e;
  font-size: 13px;
  font-weight: 700;
}
.form-group input,
.form-group select {
  height: 44px;
  padding: 0 12px;
}
.form-group textarea {
  resize: vertical;
  padding: 12px;
}
.form-group small {
  display: block;
  margin-top: 7px;
  color: #a06000;
  font-size: 11px;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 11px;
  padding-top: 8px;
}
.history {
  max-height: 170px;
  overflow-y: auto;
  margin-bottom: 16px;
  padding: 14px;
  border: 1px solid #e4ebf1;
  border-radius: 10px;
  background: #f8fafc;
}
.history > strong {
  display: block;
  margin-bottom: 10px;
  font-size: 13px;
}
.history-item {
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 9px 0;
  border-top: 1px solid #e4ebf1;
}
.history-item span {
  font-size: 11px;
  font-weight: 800;
}
.history-item small {
  color: #6b7e90;
  font-size: 11px;
}
@media (max-width: 1000px) {
  .panel-header,
  .filters {
    align-items: stretch;
    flex-direction: column;
  }
  .search {
    max-width: none;
  }
  .filters select {
    width: 100%;
  }
}
@media (max-width: 760px) {
  .app-layout {
    grid-template-columns: 1fr;
  }
  .sidebar {
    position: static;
    height: auto;
  }
  .menu,
  .logout-button {
    display: none;
  }
  .content {
    padding: 22px 16px;
  }
  .user strong {
    display: none;
  }
  .form-row {
    grid-template-columns: 1fr;
    gap: 0;
  }
}
.sidebar {
  min-height: 100vh;
  height: 100vh;
  display: flex;
  flex-direction: column;
}

.sidebar .brand {
  flex-shrink: 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.25);
  padding-bottom: 20px;
  margin-bottom: 12px;
}

.sidebar .menu {
  display: flex;
  flex: 1 1 auto;
  min-height: 0;
  flex-direction: column;
  justify-content: space-around;
  gap: 0;
}

.sidebar .menu-item {
  display: flex;
  width: 100%;
  min-height: 48px;
  align-items: center;
  gap: 13px;
  padding: 12px 15px;
  border-radius: 10px;
  transition: background 0.2s ease;
}

.sidebar .menu-item.active {
  border-left: 4px solid #ffffff;
  padding-left: 11px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.18);
  font-weight: 700;
}

.sidebar .logout-button {
  flex-shrink: 0;
  margin-top: 12px;
  min-height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: 10px;
  color: #ffffff;
  background: transparent;
  font-weight: 600;
}

.sidebar .logout-button:hover {
  background: rgba(255, 255, 255, 0.14);
}

@media (max-width: 900px) {
  .sidebar {
    min-height: auto;
    height: auto;
  }

  .sidebar .menu {
    flex: initial;
    gap: 8px;
  }
}
</style>