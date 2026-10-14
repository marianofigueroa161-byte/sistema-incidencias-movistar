<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
const router = useRouter()
const authStore = useAuthStore()
const incidencias = ref([])
const cargando = ref(true)
const mensaje = ref('')
const fechaInicio = ref('')
const fechaFin = ref('')
const filtroEstado = ref('')
const incidenciasFiltradas = computed(() => incidencias.value.filter((incidencia) => {
  const fecha = incidencia.created_at ? new Date(incidencia.created_at) : null
  const inicio = fechaInicio.value ? new Date(`${fechaInicio.value}T00:00:00`) : null
  const fin = fechaFin.value ? new Date(`${fechaFin.value}T23:59:59`) : null
  const cumpleInicio = !inicio || (fecha && fecha >= inicio)
  const cumpleFin = !fin || (fecha && fecha <= fin)
  const cumpleEstado = !filtroEstado.value || incidencia.estado === filtroEstado.value
  return cumpleInicio && cumpleFin && cumpleEstado
}))
const resumen = computed(() => ({
  total: incidenciasFiltradas.value.length,
  pendientes: incidenciasFiltradas.value.filter((item) => ['REGISTRADO', 'ASIGNADO'].includes(item.estado)).length,
  enProceso: incidenciasFiltradas.value.filter((item) => item.estado === 'EN_PROCESO').length,
  solucionadas: incidenciasFiltradas.value.filter((item) => ['SOLUCIONADO', 'CERRADO'].includes(item.estado)).length,
}))
const prioridades = computed(() => ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'].map((nombre) => ({
  nombre,
  total: incidenciasFiltradas.value.filter((item) => item.prioridad === nombre).length,
})))
const categorias = computed(() => {
  const totales = {}
  incidenciasFiltradas.value.forEach((item) => {
    const nombre = item.categoria || 'Sin categoría'
    totales[nombre] = (totales[nombre] || 0) + 1
  })
  return Object.entries(totales).map(([nombre, total]) => ({ nombre, total })).sort((a, b) => b.total - a.total)
})
const porcentaje = (total) => resumen.value.total ? Math.round((total / resumen.value.total) * 100) : 0
const cargarReporte = async () => {
  cargando.value = true
  mensaje.value = ''
  try {
    const respuesta = await api.get('/incidencias')
    incidencias.value = respuesta.data.incidencias || []
  } catch (error) {
    if (error.response?.status === 401) {
      await authStore.cerrarSesion()
      router.push('/login')
      return
    }
    mensaje.value = error.response?.data?.message || 'No fue posible cargar el reporte.'
  } finally {
    cargando.value = false
  }
}
const limpiarFiltros = () => {
  fechaInicio.value = ''
  fechaFin.value = ''
  filtroEstado.value = ''
}
const formatearFecha = (fecha) => {
  if (!fecha) return '-'
  return new Intl.DateTimeFormat('es-PE', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(fecha))
}
const textoEstado = (estado) => estado?.replaceAll('_', ' ') || '-'
const escaparCSV = (valor) => `"${String(valor ?? '').replaceAll('"', '""')}"`
const exportarCSV = () => {
  if (!incidenciasFiltradas.value.length) {
    mensaje.value = 'No existen datos para exportar.'
    return
  }
  const encabezados = ['Código', 'Cliente', 'Asunto', 'Categoría', 'Prioridad', 'Estado', 'Técnico', 'Fecha']
  const filas = incidenciasFiltradas.value.map((item) => [
    item.codigo,
    `${item.cliente_nombres || ''} ${item.cliente_apellidos || ''}`.trim(),
    item.asunto,
    item.categoria,
    item.prioridad,
    textoEstado(item.estado),
    item.tecnico_nombres ? `${item.tecnico_nombres} ${item.tecnico_apellidos || ''}`.trim() : 'Sin asignar',
    formatearFecha(item.created_at),
  ])
  const contenido = [encabezados, ...filas].map((fila) => fila.map(escaparCSV).join(',')).join('\r\n')
  const archivo = new Blob([`\uFEFF${contenido}`], { type: 'text/csv;charset=utf-8' })
  const enlace = document.createElement('a')
  enlace.href = URL.createObjectURL(archivo)
  enlace.download = `reporte-incidencias-${new Date().toISOString().slice(0, 10)}.csv`
  enlace.click()
  URL.revokeObjectURL(enlace.href)
}
const cerrarSesion = async () => {
  await authStore.cerrarSesion()
  router.push('/login')
}
onMounted(cargarReporte)
</script>
<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="brand"><div class="brand-icon">M</div><strong>Movistar</strong></div>
      <nav class="menu">
        <button class="menu-item" type="button" @click="router.push('/dashboard')"><i class="bi bi-grid-1x2-fill"></i>Panel principal</button>
        <button class="menu-item" type="button" @click="router.push('/incidencias')"><i class="bi bi-ticket-perforated"></i>Incidencias</button>
        <button class="menu-item" type="button" @click="router.push('/usuarios')"><i class="bi bi-people"></i>Usuarios</button>
        <button class="menu-item" type="button" @click="router.push('/tecnicos')"><i class="bi bi-person-gear"></i>Técnicos</button>
        <button class="menu-item active" type="button"><i class="bi bi-bar-chart"></i>Reportes</button>
        <button class="menu-item" type="button" @click="router.push('/configuracion')"><i class="bi bi-gear"></i>Configuración</button>
      </nav>
      <button class="logout-button" type="button" @click="cerrarSesion"><i class="bi bi-box-arrow-left"></i>Cerrar sesión</button>
    </aside>
    <main class="content">
      <header class="topbar">ss
        <div></div>
        <div class="user"><strong>{{ authStore.usuario?.nombres }} {{ authStore.usuario?.apellidos }}</strong><div class="avatar">{{ authStore.usuario?.nombres?.charAt(0) }}</div></div>
      </header>
      <div v-if="mensaje" class="alerta"><i class="bi bi-exclamation-circle"></i>{{ mensaje }}</div>
      <section class="filters-card">
        <div class="filter-group"><label>Desde</label><input v-model="fechaInicio" type="date" /></div>
        <div class="filter-group"><label>Hasta</label><input v-model="fechaFin" type="date" /></div>
        <div class="filter-group"><label>Estado</label><select v-model="filtroEstado"><option value="">Todos los estados</option><option value="REGISTRADO">Registrado</option><option value="ASIGNADO">Asignado</option><option value="EN_PROCESO">En proceso</option><option value="SOLUCIONADO">Solucionado</option><option value="CERRADO">Cerrado</option></select></div>
        <button class="secondary-button" type="button" @click="fechaInicio = ''; fechaFin = ''; filtroEstado = ''; mensaje = ''"><i class="bi bi-arrow-counterclockwise"></i>Limpiar</button>
        <button class="primary-button" type="button" @click="exportarCSV"><i class="bi bi-download"></i>Exportar CSV</button>
      </section>
      <div v-if="cargando" class="loading"><span class="spinner-border"></span>Cargando reporte...</div>
      <template v-else>
        <section class="statistics">
          <article class="stat-card"><div class="stat-icon blue"><i class="bi bi-ticket-perforated"></i></div><div><span>Total de incidencias</span><strong>{{ resumen.total }}</strong></div></article>
          <article class="stat-card"><div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div><div><span>Pendientes</span><strong>{{ resumen.pendientes }}</strong></div></article>
          <article class="stat-card"><div class="stat-icon purple"><i class="bi bi-tools"></i></div><div><span>En proceso</span><strong>{{ resumen.enProceso }}</strong></div></article>
          <article class="stat-card"><div class="stat-icon green"><i class="bi bi-check-circle"></i></div><div><span>Solucionadas</span><strong>{{ resumen.solucionadas }}</strong></div></article>
        </section>
        <section class="report-grid">
          <article class="chart-card">
            <h3>Incidencias por prioridad</h3>
            <div v-if="!resumen.total" class="empty-small">No existen datos registrados</div>
            <div v-for="item in prioridades" v-else :key="item.nombre" class="bar-row"><div class="bar-label"><span>{{ item.nombre }}</span><strong>{{ item.total }} ({{ porcentaje(item.total) }}%)</strong></div><div class="bar-track"><div class="bar-fill priority" :style="{ width: `${porcentaje(item.total)}%` }"></div></div></div>
          </article>
          <article class="chart-card">
            <h3>Incidencias por categoría</h3>
            <div v-if="!categorias.length" class="empty-small">No existen datos registrados</div>
            <div v-for="item in categorias" v-else :key="item.nombre" class="bar-row"><div class="bar-label"><span>{{ item.nombre }}</span><strong>{{ item.total }} ({{ porcentaje(item.total) }}%)</strong></div><div class="bar-track"><div class="bar-fill category" :style="{ width: `${porcentaje(item.total)}%` }"></div></div></div>
          </article>
        </section>
        <section class="table-card">
          <div class="table-header"><h3>Detalle de incidencias</h3><span>{{ incidenciasFiltradas.length }} registros</span></div>
          <div v-if="!incidenciasFiltradas.length" class="empty-state"><i class="bi bi-inbox"></i><strong>No existen incidencias para los filtros seleccionados</strong></div>
          <div v-else class="table-container"><table><thead><tr><th>Código</th><th>Cliente</th><th>Categoría</th><th>Prioridad</th><th>Estado</th><th>Técnico</th><th>Fecha</th></tr></thead><tbody><tr v-for="item in incidenciasFiltradas" :key="item.id"><td><strong>{{ item.codigo }}</strong></td><td>{{ item.cliente_nombres }} {{ item.cliente_apellidos }}</td><td>{{ item.categoria }}</td><td>{{ item.prioridad }}</td><td>{{ textoEstado(item.estado) }}</td><td>{{ item.tecnico_nombres ? `${item.tecnico_nombres} ${item.tecnico_apellidos}` : 'Sin asignar' }}</td><td>{{ formatearFecha(item.created_at) }}</td></tr></tbody></table></div>
        </section>
      </template>
    </main>
  </div>
</template>
<style scoped>
* { box-sizing: border-box; }
.app-layout { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; color: #18324a; background: #f4f7fa; font-family: Arial, Helvetica, sans-serif; }
.sidebar { position: sticky; top: 0; display: flex; height: 100vh; flex-direction: column; padding: 28px 18px; color: white; background: linear-gradient(180deg, #087ebc, #005d91); }
.brand { display: flex; align-items: center; gap: 12px; padding: 0 12px 32px; font-size: 22px; }
.brand-icon { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 13px; color: #0783c2; background: white; font-size: 24px; font-weight: 800; }
.menu { display: grid; gap: 7px; }
.menu-item, .logout-button { display: flex; width: 100%; align-items: center; gap: 13px; padding: 13px 15px; border: 0; border-radius: 10px; color: rgba(255,255,255,.82); background: transparent; font-size: 14px; text-align: left; cursor: pointer; }
.menu-item:hover, .menu-item.active, .logout-button:hover { color: white; background: rgba(255,255,255,.14); }
.logout-button { margin-top: auto; }
.content { min-width: 0; padding: 30px 36px; }
.topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 26px; }
.user { display: flex; align-items: center; gap: 12px; font-size: 14px; }
.avatar { display: grid; width: 43px; height: 43px; place-items: center; border-radius: 50%; color: white; background: #019be1; font-weight: 800; }
.alerta { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 13px 16px; border-radius: 10px; color: #9d3737; background: #fdeaea; font-size: 13px; }
.filters-card { display: flex; align-items: flex-end; gap: 12px; margin-bottom: 20px; padding: 18px; border: 1px solid #e3eaf0; border-radius: 15px; background: white; }
.filter-group { flex: 1; }
.filter-group label { display: block; margin-bottom: 7px; color: #536577; font-size: 12px; font-weight: 700; }
.filter-group input, .filter-group select { width: 100%; height: 42px; padding: 0 11px; border: 1px solid #d9e2ea; border-radius: 9px; outline: none; color: #20384d; background: white; }
.primary-button, .secondary-button { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; gap: 8px; padding: 0 15px; border-radius: 9px; font-size: 12px; font-weight: 700; white-space: nowrap; cursor: pointer; }
.primary-button { border: 0; color: white; background: #019be1; }
.secondary-button { border: 1px solid #d8e1e9; color: #536577; background: white; }
.loading { display: flex; min-height: 500px; align-items: center; justify-content: center; flex-direction: column; gap: 10px; color: #8494a3; }
.statistics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 20px; }
.stat-card { display: flex; align-items: center; gap: 15px; padding: 22px; border: 1px solid #e3eaf0; border-radius: 15px; background: white; }
.stat-card span, .stat-card strong { display: block; }
.stat-card span { margin-bottom: 6px; color: #667a8e; font-size: 12px; }
.stat-card strong { color: #122b43; font-size: 24px; }
.stat-icon { display: grid; width: 48px; height: 48px; place-items: center; border-radius: 13px; font-size: 20px; }
.blue { color: #019be1; background: #e2f4fc; }.orange { color: #ef8c00; background: #fff0d8; }.purple { color: #7451c9; background: #eee8ff; }.green { color: #0d9a69; background: #e0f5ed; }
.report-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.chart-card, .table-card { border: 1px solid #e3eaf0; border-radius: 15px; background: white; }
.chart-card { min-height: 290px; padding: 22px; }
.chart-card h3, .table-header h3 { margin: 0 0 22px; font-size: 16px; }
.bar-row { margin-bottom: 18px; }.bar-label { display: flex; justify-content: space-between; margin-bottom: 7px; font-size: 12px; }.bar-track { overflow: hidden; height: 8px; border-radius: 20px; background: #edf2f6; }.bar-fill { height: 100%; border-radius: 20px; }.priority { background: linear-gradient(90deg,#019be1,#0075b5); }.category { background: linear-gradient(90deg,#7451c9,#9d7bea); }
.empty-small { display: grid; min-height: 190px; color: #8494a3; place-items: center; font-size: 13px; }
.table-card { overflow: hidden; min-height: 330px; }.table-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 22px; border-bottom: 1px solid #edf1f4; }.table-header h3 { margin: 0; }.table-header span { color: #718295; font-size: 12px; }
.table-container { overflow-x: auto; } table { width: 100%; border-collapse: collapse; } th, td { padding: 14px 17px; border-bottom: 1px solid #edf1f4; font-size: 12px; text-align: left; white-space: nowrap; } th { color: #6b7e90; background: #f8fafc; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
.empty-state { display: flex; min-height: 250px; align-items: center; justify-content: center; flex-direction: column; gap: 10px; color: #8494a3; }.empty-state i { color: #b8c5cf; font-size: 40px; }
@media (max-width: 1050px) { .statistics { grid-template-columns: 1fr 1fr; }.filters-card { align-items: stretch; flex-wrap: wrap; }.filter-group { min-width: 210px; } }
@media (max-width: 760px) { .app-layout { grid-template-columns: 1fr; }.sidebar { position: static; height: auto; }.menu, .logout-button { display: none; }.content { padding: 22px 16px; }.user strong { display: none; }.statistics, .report-grid { grid-template-columns: 1fr; }.filters-card { flex-direction: column; }.filter-group, .primary-button, .secondary-button { width: 100%; } }
</style>