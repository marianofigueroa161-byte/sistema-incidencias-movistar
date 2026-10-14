<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
const router = useRouter()
const authStore = useAuthStore()
const mensaje = ref('')
const configuracion = reactive({ notificaciones: true, actualizacionAutomatica: true, filasPorPagina: '10' })
onMounted(() => {
  const guardada = localStorage.getItem('configuracionSistema')
  if (guardada) Object.assign(configuracion, JSON.parse(guardada))
})
const guardarConfiguracion = () => {
  localStorage.setItem('configuracionSistema', JSON.stringify(configuracion))
  mensaje.value = 'Configuración guardada correctamente.'
  setTimeout(() => { mensaje.value = '' }, 3000)
}
const cerrarSesion = async () => {
  await authStore.cerrarSesion()
  router.push('/login')
}
</script>
<template>
  <div class="app-layout" translate="no">
    <aside class="sidebar">
      <div class="brand"><div class="brand-icon">M</div><strong>Movistar</strong></div>
      <nav class="menu">
        <button class="menu-item" type="button" @click="router.push('/dashboard')"><i class="bi bi-grid-1x2-fill"></i>Panel principal</button>
        <button class="menu-item" type="button" @click="router.push('/incidencias')"><i class="bi bi-ticket-perforated"></i>Incidencias</button>
        <button class="menu-item" type="button" @click="router.push('/usuarios')"><i class="bi bi-people"></i>Usuarios</button>
        <button class="menu-item" type="button" @click="router.push('/tecnicos')"><i class="bi bi-person-gear"></i>Técnicos</button>
        <button class="menu-item" type="button" @click="router.push('/reportes')"><i class="bi bi-bar-chart"></i>Reportes</button>
        <button class="menu-item active" type="button"><i class="bi bi-gear"></i>Configuración</button>
      </nav>
      <button class="logout-button" type="button" @click="cerrarSesion"><i class="bi bi-box-arrow-left"></i>Cerrar sesión</button>
    </aside>
    <main class="content">
      <header class="topbar"><div></div><div class="user"><strong>{{ authStore.usuario?.nombres }} {{ authStore.usuario?.apellidos }}</strong><div class="avatar">{{ authStore.usuario?.nombres?.charAt(0) }}</div></div></header>
      <div v-if="mensaje" class="alerta"><i class="bi bi-check-circle"></i>{{ mensaje }}</div>
      <section class="settings-grid">
        <article class="card">
          <div class="card-title"><div class="title-icon purple"><i class="bi bi-sliders"></i></div><div><h2>Preferencias</h2></div></div>
          <label class="setting-row"><div><strong>Notificaciones</strong></div><input v-model="configuracion.notificaciones" type="checkbox" /></label>
          <label class="setting-row"><div><strong>Actualización automática</strong><span>Mantener actualizada la información mostrada.</span></div><input v-model="configuracion.actualizacionAutomatica" type="checkbox" /></label>
          <label class="select-row"><div><strong>Registros por página</strong></div><select v-model="configuracion.filasPorPagina"><option value="10">10 registros</option><option value="20">20 registros</option><option value="50">50 registros</option></select></label>
        </article>
        <article class="card system-card">
          <div class="card-title"><div class="title-icon green"><i class="bi bi-info-circle"></i></div><div><h2>Información del sistema</h2></div></div>
          <div class="info-row"><span>Sistema</span><strong>Gestión de Incidencias Movistar</strong></div>
          <div class="info-row"><span>Versión</span><strong>1.0.0</strong></div>
          <div class="info-row"><span>Entorno</span><strong>Desarrollo local</strong></div>
        </article>
      </section>
      <div class="actions"><button class="primary-button" type="button" @click="guardarConfiguracion"><i class="bi bi-check-lg"></i>Guardar configuración</button></div>
    </main>
  </div>
</template>
<style scoped>
* { box-sizing: border-box; }
.app-layout { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; color: #18324a; background: #f4f7fa; font-family: Arial, Helvetica, sans-serif; }
.sidebar { position: sticky; top: 0; display: flex; height: 100vh; flex-direction: column; padding: 28px 18px; color: white; background: linear-gradient(180deg, #087ebc, #005d91); }
.brand { display: flex; align-items: center; gap: 12px; padding: 0 12px 32px; font-size: 22px; }.brand-icon { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 13px; color: #0783c2; background: white; font-size: 24px; font-weight: 800; }
.menu { display: grid; gap: 7px; }.menu-item,.logout-button { display: flex; width: 100%; align-items: center; gap: 13px; padding: 13px 15px; border: 0; border-radius: 10px; color: rgba(255,255,255,.82); background: transparent; font-size: 14px; text-align: left; cursor: pointer; }.menu-item:hover,.menu-item.active,.logout-button:hover { color: white; background: rgba(255,255,255,.14); }.logout-button { margin-top: auto; }
.content { min-width: 0; padding: 30px 36px; }.topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }.topbar h1 { margin: 0 0 6px; color: #112b42; font-size: 27px; }.topbar p,.card-title p { margin: 0; color: #708294; font-size: 13px; }.user { display: flex; align-items: center; gap: 12px; font-size: 14px; }.avatar { display: grid; width: 43px; height: 43px; place-items: center; border-radius: 50%; color: white; background: #019be1; font-weight: 800; }
.alerta { display: flex; align-items: center; gap: 9px; margin-bottom: 18px; padding: 13px 16px; border-radius: 10px; color: #087754; background: #e2f6ee; font-size: 13px; }.settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }.card { padding: 23px; border: 1px solid #e1e9ef; border-radius: 15px; background: white; }.system-card { grid-column: auto; }.card-title { display: flex; align-items: center; gap: 13px; margin-bottom: 22px; }.card-title h2 { margin: 0 0 5px; color: #17324a; font-size: 17px; }.title-icon { display: grid; width: 43px; height: 43px; place-items: center; border-radius: 12px; font-size: 19px; }.purple { color: #7451c9; background: #eee8ff; }.green { color: #0d9a69; background: #e0f5ed; }
.setting-row strong,.setting-row span,.select-row strong,.select-row span { display: block; }.setting-row span,.select-row span { margin-top: 5px; color: #748596; font-size: 12px; }.info-row { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #edf1f4; font-size: 13px; }.info-row:last-child { border-bottom: 0; }
.setting-row,.select-row { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 16px 0; border-bottom: 1px solid #edf1f4; }.setting-row input { width: 18px; height: 18px; accent-color: #019be1; }.select-row { border-bottom: 0; }.select-row select { min-width: 135px; height: 39px; padding: 0 10px; border: 1px solid #d7e1e8; border-radius: 8px; color: #294258; background: white; }.actions { display: flex; justify-content: flex-end; margin-top: 20px; }.primary-button { display: inline-flex; min-height: 43px; align-items: center; gap: 8px; padding: 0 18px; border: 0; border-radius: 9px; color: white; background: #019be1; font-weight: 700; cursor: pointer; }
@media (max-width: 900px) { .settings-grid { grid-template-columns: 1fr; }.system-card { grid-column: auto; } }
@media (max-width: 760px) { .app-layout { grid-template-columns: 1fr; }.sidebar { position: static; height: auto; }.menu,.logout-button { display: none; }.content { padding: 22px 16px; }.topbar h1 { font-size: 23px; }.user strong { display: none; } }
</style>