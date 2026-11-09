<script setup>
import { computed } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const mostrarMenu = computed(() => ['dashboard', 'incidencias', 'usuarios', 'tecnicos', 'reportes'].includes(String(route.name)))
const enlaces = [
  { nombre: 'dashboard', ruta: '/dashboard', texto: 'Panel principal', icono: 'bi-grid-1x2-fill' },
  { nombre: 'incidencias', ruta: '/incidencias', texto: 'Incidencias', icono: 'bi-ticket-perforated' },
  { nombre: 'usuarios', ruta: '/usuarios', texto: 'Usuarios', icono: 'bi-people' },
  { nombre: 'tecnicos', ruta: '/tecnicos', texto: 'Técnicos', icono: 'bi-person-gear' },
  { nombre: 'reportes', ruta: '/reportes', texto: 'Reportes', icono: 'bi-bar-chart' },
]
const cerrandoSesion = computed(() => authStore.cargando)
const cerrarSesion = async () => {
  await authStore.cerrarSesion()
  await router.push('/login')
}
</script>

<template>
  <div v-if="mostrarMenu" class="movistar-shell">
    <aside class="movistar-sidebar">
      <div class="movistar-brand">
        <div class="movistar-brand-icon">M</div>
        <strong>Movistar</strong>
      </div>
      <nav class="movistar-menu" aria-label="Navegación principal">
        <button
          v-for="enlace in enlaces"
          :key="enlace.nombre"
          class="movistar-menu-item"
          :class="{ active: route.name === enlace.nombre }"
          :aria-current="route.name === enlace.nombre ? 'page' : undefined"
          type="button"
          @click="router.push(enlace.ruta)"
        >
          <i class="bi" :class="enlace.icono"></i>{{ enlace.texto }}
        </button>
      </nav>
      <button class="movistar-logout" type="button" :disabled="cerrandoSesion" @click="cerrarSesion">
        <i class="bi bi-box-arrow-left"></i>Cerrar sesión
      </button>
    </aside>
    <div class="movistar-view"><RouterView /></div>
  </div>
  <RouterView v-else />
</template>
