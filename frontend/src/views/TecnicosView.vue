<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
const router = useRouter()
const authStore = useAuthStore()
const tecnicos = ref([])
const busqueda = ref('')
const cargando = ref(true)
const guardando = ref(false)
const mostrarFormulario = ref(false)
const mensaje = ref('')
const tipoMensaje = ref('')
const formulario = reactive({ tipo_documento: 'DNI', numero_documento: '', nombres: '', apellidos: '', correo: '', telefono: '', clave: '' })
const tecnicosFiltrados = computed(() => {
  const texto = busqueda.value.toLowerCase().trim()
  if (!texto) return tecnicos.value
  return tecnicos.value.filter((tecnico) => {
    const nombre = `${tecnico.nombres} ${tecnico.apellidos}`.toLowerCase()
    return nombre.includes(texto) || tecnico.numero_documento?.toLowerCase().includes(texto) || tecnico.correo?.toLowerCase().includes(texto)
  })
})
const cargarTecnicos = async () => {
  cargando.value = true
  try {
    const respuesta = await api.get('/tecnicos')
    tecnicos.value = respuesta.data.tecnicos
  } catch (error) {
    if (error.response?.status === 401) {
      await authStore.cerrarSesion()
      router.push('/login')
      return
    }
    tipoMensaje.value = 'error'
    mensaje.value = error.response?.data?.message || 'No se pudieron cargar los técnicos.'
  } finally {
    cargando.value = false
  }
}
const limpiarFormulario = () => {
  Object.assign(formulario, { tipo_documento: 'DNI', numero_documento: '', nombres: '', apellidos: '', correo: '', telefono: '', clave: '' })
}
const abrirFormulario = () => {
  mensaje.value = ''
  limpiarFormulario()
  mostrarFormulario.value = true
}
const cerrarFormulario = () => {
  if (!guardando.value) mostrarFormulario.value = false
}
const registrarTecnico = async () => {
  guardando.value = true
  mensaje.value = ''
  try {
    const respuesta = await api.post('/tecnicos', formulario)
    tipoMensaje.value = 'success'
    mensaje.value = respuesta.data.message
    mostrarFormulario.value = false
    await cargarTecnicos()
  } catch (error) {
    tipoMensaje.value = 'error'
    mensaje.value = error.response?.data?.message || 'No se pudo registrar el técnico.'
  } finally {
    guardando.value = false
  }
}
const cerrarSesion = async () => {
  await authStore.cerrarSesion()
  router.push('/login')
}
onMounted(cargarTecnicos)
</script>
<template>
  <div class="app-layout" translate="no">
    <aside class="sidebar">
      <div class="brand"><div class="brand-icon">M</div><strong>Movistar</strong></div>
      <nav class="menu">
        <button class="menu-item" type="button" @click="router.push('/dashboard')"><i class="bi bi-grid-1x2-fill"></i>Panel principal</button>
        <button class="menu-item" type="button" @click="router.push('/incidencias')"><i class="bi bi-ticket-perforated"></i>Incidencias</button>
        <button class="menu-item" type="button" @click="router.push('/usuarios')"><i class="bi bi-people"></i>Usuarios</button>
        <button class="menu-item active" type="button"><i class="bi bi-person-gear"></i>Técnicos</button>
        <button class="menu-item" type="button" @click="router.push('/reportes')"><i class="bi bi-bar-chart"></i>Reportes</button>
        <button class="menu-item" type="button"><i class="bi bi-gear"></i>Configuración</button>
      </nav>
      <button class="logout-button" type="button" @click="cerrarSesion"><i class="bi bi-box-arrow-left"></i>Cerrar sesión</button>
    </aside>
    <main class="content">
      <header class="topbar">
        <div></div>
        <div class="user"><strong>{{ authStore.usuario?.nombres }} {{ authStore.usuario?.apellidos }}</strong><div class="avatar">{{ authStore.usuario?.nombres?.charAt(0) }}</div></div>
      </header>
      <div v-if="mensaje" :class="['alerta', tipoMensaje]"><i :class="tipoMensaje === 'success' ? 'bi bi-check-circle' : 'bi bi-exclamation-circle'"></i>{{ mensaje }}</div>
      <section class="panel">
        <div class="panel-header">
          <div class="search"><i class="bi bi-search"></i><input v-model="busqueda" type="search" placeholder="Buscar por nombre, documento o correo" /></div>
          <button class="primary-button" type="button" @click="abrirFormulario"><i class="bi bi-person-plus"></i>Registrar técnico</button>
        </div>
        <div v-if="cargando" class="loading"><span class="spinner-border"></span>Cargando técnicos...</div>
        <div v-else-if="tecnicosFiltrados.length === 0" class="empty-state"><i class="bi bi-person-gear"></i><strong>No existen técnicos registrados</strong></div>
        <div v-else class="table-container">
          <table>
            <thead><tr><th>Documento</th><th>Técnico</th><th>Correo</th><th>Teléfono</th><th>Incidencias asignadas</th><th>Estado</th></tr></thead>
            <tbody>
              <tr v-for="tecnico in tecnicosFiltrados" :key="tecnico.id">
                <td>{{ tecnico.tipo_documento }} {{ tecnico.numero_documento }}</td>
                <td><strong>{{ tecnico.nombres }} {{ tecnico.apellidos }}</strong></td>
                <td>{{ tecnico.correo }}</td>
                <td>{{ tecnico.telefono || '-' }}</td>
                <td>{{ tecnico.incidencias_asignadas }}</td>
                <td><span :class="['estado', tecnico.estado.toLowerCase()]">{{ tecnico.estado }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
    <div v-if="mostrarFormulario" class="modal-backdrop" @click.self="cerrarFormulario">
      <section class="modal-card">
        <header class="modal-header"><h2>Registrar técnico</h2><button type="button" :disabled="guardando" @click="cerrarFormulario"><i class="bi bi-x-lg"></i></button></header>
        <form @submit.prevent="registrarTecnico">
          <div class="form-row document-row">
            <div class="form-group"><label>Tipo de documento</label><select v-model="formulario.tipo_documento" required><option value="DNI">DNI</option><option value="CE">Carné de extranjería</option><option value="PASAPORTE">Pasaporte</option></select></div>
            <div class="form-group"><label>Número de documento</label><input v-model.trim="formulario.numero_documento" type="text" maxlength="20" required /></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Nombres</label><input v-model.trim="formulario.nombres" type="text" maxlength="100" required /></div>
            <div class="form-group"><label>Apellidos</label><input v-model.trim="formulario.apellidos" type="text" maxlength="100" required /></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Correo electrónico</label><input v-model.trim="formulario.correo" type="email" maxlength="150" required /></div>
            <div class="form-group"><label>Teléfono</label><input v-model.trim="formulario.telefono" type="text" maxlength="20" /></div>
          </div>
          <div class="form-group"><label>Contraseña inicial</label><input v-model="formulario.clave" type="password" minlength="8" autocomplete="new-password" required /></div>
          <footer class="modal-actions"><button class="secondary-button" type="button" :disabled="guardando" @click="cerrarFormulario">Cancelar</button><button class="primary-button" type="submit" :disabled="guardando">{{ guardando ? 'Guardando...' : 'Registrar técnico' }}</button></footer>
        </form>
      </section>
    </div>
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
.topbar, .user, .panel-header { display: flex; align-items: center; }
.topbar, .panel-header { justify-content: space-between; }
.topbar { margin-bottom: 26px; }
.user { gap: 12px; font-size: 14px; }
.avatar { display: grid; width: 43px; height: 43px; place-items: center; border-radius: 50%; color: white; background: #019be1; font-weight: 800; }
.alerta { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; padding: 13px 16px; border-radius: 10px; font-size: 13px; }
.alerta.success { color: #136b49; background: #e4f7ee; }
.alerta.error { color: #9d3737; background: #fdeaea; }
.panel { overflow: hidden; min-height: 620px; border: 1px solid #e4ebf1; border-radius: 17px; background: white; }
.panel-header { gap: 18px; padding: 22px; border-bottom: 1px solid #edf1f4; }
.search { position: relative; width: min(100%, 430px); }
.search i { position: absolute; top: 50%; left: 14px; color: #8494a3; transform: translateY(-50%); }
.search input { width: 100%; height: 43px; padding: 0 14px 0 41px; border: 1px solid #d9e2ea; border-radius: 10px; outline: none; }
.primary-button, .secondary-button { min-height: 43px; padding: 0 17px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; }
.primary-button { border: 0; color: white; background: #019be1; }
.secondary-button { border: 1px solid #d8e1e9; color: #536577; background: white; }
.loading, .empty-state { display: flex; min-height: 500px; align-items: center; justify-content: center; flex-direction: column; gap: 10px; color: #8494a3; }
.empty-state i { font-size: 44px; }
.table-container { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 15px 18px; border-bottom: 1px solid #edf1f4; font-size: 12px; text-align: left; white-space: nowrap; }
th { color: #6b7e90; background: #f8fafc; font-size: 11px; text-transform: uppercase; }
.estado { display: inline-flex; padding: 5px 9px; border-radius: 20px; font-size: 10px; font-weight: 800; }
.estado.activo { color: #13704b; background: #ddf5e9; }
.estado.inactivo, .estado.bloqueado { color: #9d3737; background: #fdeaea; }
.modal-backdrop { position: fixed; z-index: 1000; inset: 0; display: grid; padding: 30px 20px; overflow-y: auto; background: rgba(15,35,52,.55); place-items: center; }
.modal-card { width: min(100%, 680px); border-radius: 18px; background: white; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 22px 26px; border-bottom: 1px solid #edf1f4; }
.modal-header h2 { margin: 0; font-size: 22px; }
.modal-header button { border: 0; background: transparent; cursor: pointer; }
.modal-card form { padding: 24px 26px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.document-row { grid-template-columns: 180px 1fr; }
.form-group { margin-bottom: 17px; }
.form-group label { display: block; margin-bottom: 7px; font-size: 13px; font-weight: 700; }
.form-group input, .form-group select { width: 100%; height: 44px; padding: 0 12px; border: 1px solid #d9e2ea; border-radius: 10px; outline: none; }
.form-group input:focus, .form-group select:focus, .search input:focus { border-color: #019be1; box-shadow: 0 0 0 3px rgba(1,155,225,.1); }
.modal-actions { display: flex; justify-content: flex-end; gap: 11px; padding-top: 10px; }
@media (max-width: 760px) { .app-layout { grid-template-columns: 1fr; } .sidebar { height: auto; } .menu, .logout-button { display: none; } .content { padding: 22px 16px; } .panel-header, .form-row, .document-row { grid-template-columns: 1fr; align-items: stretch; } .panel-header { flex-direction: column; } .search { width: 100%; } }
</style>f