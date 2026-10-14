<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
const router = useRouter()
const authStore = useAuthStore()
const resumen = ref({ total: 0, pendientes: 0, en_proceso: 0, solucionadas: 0 })
const incidencias = ref([])
const cargando = ref(true)
const mensaje = ref('')
const incidenciasRecientes = computed(() => incidencias.value.slice(0, 5))
const cargarDashboard = async () => {
  cargando.value = true
  mensaje.value = ''
  try {
    const [respuestaResumen, respuestaIncidencias] = await Promise.all([api.get('/incidencias/resumen'), api.get('/incidencias')])
    resumen.value = respuestaResumen.data.resumen || resumen.value
    incidencias.value = respuestaIncidencias.data.incidencias || []
  } catch (error) {
    if (error.response?.status === 401) {
      await authStore.cerrarSesion()
      router.push('/login')
      return
    }
    mensaje.value = error.response?.data?.message || 'No fue posible cargar el panel principal.'
  } finally {
    cargando.value = false
  }
}
const formatearFecha = (fecha) => fecha ? new Intl.DateTimeFormat('es-PE', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(fecha)) : '-'
const textoEstado = (estado) => estado?.replaceAll('_', ' ') || '-'
const cerrarSesion = async () => {
  await authStore.cerrarSesion()
  router.push('/login')
}
onMounted(cargarDashboard)
</script>
<template>
  <div class="dashboard">
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-icon">M</div>
        <strong>Movistar</strong>
      </div>
      <nav class="menu">
        <button class="menu-item active">
          <i class="bi bi-grid-1x2-fill"></i>
          Panel principal
        </button>
        <button class="menu-item" type="button" @click="router.push('/incidencias')">
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
        <button class="menu-item" type="button" @click="router.push('/configuracion')">
          <i class="bi bi-gear"></i>
          Configuración
        </button>
      </nav>
      <button class="logout-button" @click="cerrarSesion">
        <i class="bi bi-box-arrow-left"></i>
        Cerrar sesión
      </button>
    </aside>
    <main class="content">
      <header class="topbar">
        <div>
        </div>
        <div class="user">
          <div class="user-data">
            <strong>
              {{ authStore.usuario?.nombres }}
              {{ authStore.usuario?.apellidos }}
            </strong>
          </div>
          <div class="avatar">
            {{ authStore.usuario?.nombres?.charAt(0) }}
          </div>
        </div>
      </header>
      <section class="welcome">
        <div>
          <span>BIENVENIDO AL SISTEMA</span>
          <h2>
            Hola, {{ authStore.usuario?.nombres }}
          </h2>
        </div>
        <i class="bi bi-headset"></i>
      </section>
      <section class="statistics">
        <article class="stat-card">
          <div class="stat-icon blue">
            <i class="bi bi-ticket-perforated"></i>
          </div>
          <div>
            <span>Total de incidencias</span>
            <strong>{{ resumen.total }}</strong>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon orange">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div>
            <span>Pendientes</span>
            <strong>{{ resumen.pendientes }}</strong>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon purple">
            <i class="bi bi-tools"></i>
          </div>
          <div>
            <span>En proceso</span>
            <strong>{{ resumen.en_proceso }}</strong>
          </div>
        </article>
        <article class="stat-card">
          <div class="stat-icon green">
            <i class="bi bi-check-circle"></i>
          </div>
          <div>
            <span>Solucionadas</span>
            <strong>{{ resumen.solucionadas }}</strong>
          </div>
        </article>
      </section>
      <section class="dashboard-grid">
        <article class="panel">
          <div class="panel-header">
            <div>
              <h3>Incidencias recientes</h3>
            </div>
            <button
              type="button"
              @click="router.push('/incidencias')"
            >
              Ver todas
            </button>
          </div>
          <div v-if="cargando" class="empty-state">
            <span class="spinner-border"></span>
            <strong>Cargando incidencias...</strong>
          </div>
          <div v-else-if="mensaje" class="empty-state">
            <i class="bi bi-exclamation-circle"></i>
            <strong>{{ mensaje }}</strong>
          </div>
          <div v-else-if="incidenciasRecientes.length === 0" class="empty-state">
            <i class="bi bi-inbox"></i>
            <strong>No existen incidencias registradas</strong>
          </div>
          <div v-else class="table-container">
            <table>
              <thead><tr><th>Código</th><th>Cliente</th><th>Asunto</th><th>Prioridad</th><th>Estado</th><th>Fecha</th></tr></thead>
              <tbody><tr v-for="incidencia in incidenciasRecientes" :key="incidencia.id"><td><strong>{{ incidencia.codigo }}</strong></td><td>{{ incidencia.cliente_nombres }} {{ incidencia.cliente_apellidos }}</td><td>{{ incidencia.asunto }}</td><td>{{ incidencia.prioridad }}</td><td>{{ textoEstado(incidencia.estado) }}</td><td>{{ formatearFecha(incidencia.created_at) }}</td></tr></tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
  </div>
</template>
<style scoped>
* {
  box-sizing: border-box;
}
.dashboard {
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
.menu-item.active {
  color: white;
  background: rgba(255, 255, 255, 0.14);
}
.menu-item i,
.logout-button i {
  font-size: 17px;
}
.logout-button {
  margin-top: auto;
}
.logout-button:hover {
  color: white;
  background: rgba(255, 255, 255, 0.14);
}
.content {
  padding: 30px 36px;
}
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
}
.topbar p {
  margin: 0 0 5px;
  color: #718295;
  font-size: 13px;
}
.topbar h1 {
  margin: 0;
  font-size: 27px;
}
.user {
  display: flex;
  align-items: center;
  gap: 12px;
}
.user-data {
  text-align: right;
}
.user-data strong,
.user-data span {
  display: block;
}
.user-data strong {
  font-size: 14px;
}
.user-data span {
  margin-top: 3px;
  color: #718295;
  font-size: 11px;
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
.welcome {
  display: flex;
  min-height: 180px;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 25px;
  padding: 30px 38px;
  overflow: hidden;
  border-radius: 20px;
  color: white;
  background: linear-gradient(120deg, #019be1, #0071b5);
  box-shadow: 0 15px 35px rgba(0, 113, 181, 0.2);
}
.welcome span {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1.7px;
}
.welcome h2 {
  margin: 10px 0;
  font-size: 30px;
}
.welcome p {
  max-width: 540px;
  margin: 0;
  color: rgba(255, 255, 255, 0.83);
}
.welcome > i {
  margin-right: 30px;
  font-size: 90px;
  opacity: 0.18;
}
.statistics {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  margin-bottom: 24px;
}
.stat-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 21px;
  border: 1px solid #e6edf2;
  border-radius: 16px;
  background: white;
}
.stat-icon {
  display: grid;
  width: 48px;
  height: 48px;
  flex: 0 0 48px;
  place-items: center;
  border-radius: 13px;
  font-size: 20px;
}
.stat-icon.blue {
  color: #008fd5;
  background: #e5f6fe;
}
.stat-icon.orange {
  color: #d77a00;
  background: #fff1db;
}
.stat-icon.purple {
  color: #7655c5;
  background: #f0ebff;
}
.stat-icon.green {
  color: #16885a;
  background: #e3f7ee;
}
.stat-card span,
.stat-card strong {
  display: block;
}
.stat-card span {
  margin-bottom: 5px;
  color: #718295;
  font-size: 12px;
}
.stat-card strong {
  font-size: 25px;
}
.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
}
.panel {
  min-height: 280px;
  padding: 24px;
  border: 1px solid #e6edf2;
  border-radius: 17px;
  background: white;
}
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.panel-header h3 {
  margin: 0 0 5px;
  font-size: 17px;
}
.panel-header p {
  margin: 0;
  color: #7a8b9b;
  font-size: 12px;
}
.panel-header > button {
  border: 0;
  color: #007fbe;
  background: transparent;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}
.empty-state {
  display: flex;
  min-height: 200px;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  color: #8a9aa9;
  text-align: center;
}
.empty-state i {
  margin-bottom: 13px;
  color: #b5c2cd;
  font-size: 38px;
}
.empty-state strong {
  margin-bottom: 7px;
  color: #5f7182;
  font-size: 14px;
}
.empty-state span {
  font-size: 12px;
}
.table-container {
  overflow-x: auto;
  margin-top: 20px;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th,
td {
  padding: 14px 12px;
  border-bottom: 1px solid #edf1f4;
  font-size: 12px;
  text-align: left;
  white-space: nowrap;
}
th {
  color: #6b7e90;
  background: #f8fafc;
  font-size: 10px;
  text-transform: uppercase;
}
@media (max-width: 1100px) {
  .statistics {
    grid-template-columns: repeat(2, 1fr);
  }
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 760px) {
  .dashboard {
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
    padding: 22px 18px;
  }
  .statistics {
    grid-template-columns: 1fr;
  }
  .user-data {
    display: none;
  }
}
</style>