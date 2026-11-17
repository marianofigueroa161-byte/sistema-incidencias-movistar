<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import api from '../services/api'

const abierto = ref(false)
const notificaciones = ref([])
const noLeidas = ref(0)
const cargando = ref(false)
const actualizando = ref(false)
const error = ref('')
const contenedor = ref(null)
let intervalo = null
let activo = true

let consultando = false
let cargaInicialCompleta = false

const cargar = async (silencioso = false) => {
  if (!activo || consultando || actualizando.value) return

  consultando = true

  if (!silencioso && !cargaInicialCompleta) {
    cargando.value = true
  }

  try {
    const { data } = await api.get('/notificaciones')

    if (!activo) return

    notificaciones.value = data.notificaciones ?? []
    noLeidas.value = Number(data.no_leidas ?? 0)
    cargaInicialCompleta = true
    error.value = ''
  } catch {
    if (activo) {
      error.value = 'No se pudieron cargar las notificaciones.'
    }
  } finally {
    consultando = false
    cargando.value = false
  }
}

const alternar = async () => {
  abierto.value = !abierto.value

  if (abierto.value) {
    await cargar()
  }
}

const marcarLeida = async (notificacion) => {
  if (notificacion.leida_at || actualizando.value || consultando) return

  actualizando.value = true

  try {
    await api.put(`/notificaciones/${notificacion.id}/leer`)
  } catch {
    error.value = 'No se pudo actualizar la notificación.'
    return
  } finally {
    actualizando.value = false
  }

  await cargar()
}

const marcarTodas = async () => {
  if (!noLeidas.value || actualizando.value || consultando) return

  actualizando.value = true

  try {
    await api.put('/notificaciones/leer-todas')
  } catch {
    error.value = 'No se pudieron actualizar las notificaciones.'
    return
  } finally {
    actualizando.value = false
  }

  await cargar()
}

const cerrarFuera = (evento) => {
  if (!contenedor.value?.contains(evento.target)) {
    abierto.value = false
  }
}

const cerrarEscape = (evento) => {
  if (evento.key === 'Escape') {
    abierto.value = false
  }
}

const formatoFecha = (fecha) => {
  if (!fecha) return ''

  const resultado = new Date(fecha.replace(' ', 'T'))

  if (Number.isNaN(resultado.getTime())) return fecha

  return new Intl.DateTimeFormat('es-PE', {
    dateStyle: 'short',
    timeStyle: 'short'
  }).format(resultado)
}

const iconoEstado = (estado) => ({
  REGISTRADO: 'bi-clipboard-check',
  ASIGNADO: 'bi-person-check',
  EN_PROCESO: 'bi-hourglass-split',
  SOLUCIONADO: 'bi-check-circle',
  CERRADO: 'bi-check2-all',
  CANCELADO: 'bi-x-circle'
}[estado] ?? 'bi-bell')

onMounted(() => {
  cargar()
  intervalo = window.setInterval(() => {
    if (!document.hidden) cargar(true)
  }, 5000)
  document.addEventListener('click', cerrarFuera)
  document.addEventListener('keydown', cerrarEscape)
})

onBeforeUnmount(() => {
  activo = false
  window.clearInterval(intervalo)
  document.removeEventListener('click', cerrarFuera)
  document.removeEventListener('keydown', cerrarEscape)
})
</script>

<template>
  <div ref="contenedor" class="notificaciones">
    <button
      type="button"
      class="notificaciones-campana"
      :aria-label="`Notificaciones: ${noLeidas} sin leer`"
      :aria-expanded="abierto"
      @click="alternar"
    >
      <i class="bi bi-bell"></i>
      <span v-if="noLeidas" class="notificaciones-contador">
        {{ noLeidas > 99 ? '99+' : noLeidas }}
      </span>
    </button>

    <section
      v-if="abierto"
      class="notificaciones-panel"
      aria-label="Historial de notificaciones"
    >
      <div class="notificaciones-cabecera">
        <strong>Notificaciones</strong>

        <button
          type="button"
          class="notificaciones-leer"
          :disabled="!noLeidas || actualizando || cargando"
          @click="marcarTodas"
        >
          Leer todas
        </button>
      </div>

      <div v-if="error" class="notificaciones-error" role="alert">
        {{ error }}
      </div>

      <div v-if="cargando && !notificaciones.length" class="notificaciones-vacio">
        Cargando notificaciones...
      </div>

      <div
        v-else-if="!notificaciones.length && !error"
        class="notificaciones-vacio"
      >
        <i class="bi bi-bell-slash"></i>
        <span>No tienes notificaciones.</span>
      </div>

      <div v-else class="notificaciones-listado">
        <button
          v-for="notificacion in notificaciones"
          :key="notificacion.id"
          type="button"
          class="notificaciones-item"
          :class="{ 'sin-leer': !notificacion.leida_at }"
          :disabled="actualizando || cargando"
          @click="marcarLeida(notificacion)"
        >
          <span class="notificaciones-icono">
            <i class="bi" :class="iconoEstado(notificacion.estado_nuevo)"></i>
          </span>

          <span class="notificaciones-contenido">
            <strong>{{ notificacion.titulo }}</strong>
            <span>{{ notificacion.mensaje }}</span>
            <span class="notificaciones-estado">
              {{ notificacion.estado_nuevo.replaceAll('_', ' ') }}
            </span>
            <small>{{ formatoFecha(notificacion.created_at) }}</small>
          </span>

          <span
            v-if="!notificacion.leida_at"
            class="notificaciones-punto"
            aria-label="Sin leer"
          ></span>
        </button>
      </div>

      <div class="notificaciones-pie">
        {{ noLeidas }} sin leer
      </div>
    </section>
  </div>
</template>

<style scoped>
.notificaciones {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.notificaciones-campana {
  position: relative;
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border: 0;
  border-radius: 10px;
  background: rgba(0, 123, 255, .08);
  color: inherit;
  font-size: 21px;
}

.notificaciones-campana:hover {
  background: rgba(0, 123, 255, .15);
}

.notificaciones-contador {
  position: absolute;
  top: 1px;
  right: 0;
  min-width: 18px;
  padding: 2px 4px;
  border-radius: 20px;
  background: #dc3545;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  text-align: center;
}

.notificaciones-panel {
  position: absolute;
  top: calc(100% + 12px);
  right: 0;
  z-index: 1050;
  width: min(370px, calc(100vw - 32px));
  overflow: hidden;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  color: #243447;
  box-shadow: 0 12px 35px rgba(15, 23, 42, .15);
}

.notificaciones-cabecera {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px;
  border-bottom: 1px solid #e9ecef;
}

.notificaciones-leer {
  padding: 0;
  border: 0;
  background: transparent;
  color: #007bff;
  font-size: 12px;
}

.notificaciones-leer:disabled {
  opacity: .5;
}

.notificaciones-listado {
  max-height: 420px;
  max-height: min(420px, 60vh);
  overflow-y: auto;
}

.notificaciones-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  width: 100%;
  padding: 14px 16px;
  border: 0;
  border-bottom: 1px solid #edf0f4;
  background: #fff;
  color: inherit;
  text-align: left;
}

.notificaciones-item.sin-leer {
  background: #f0f7ff;
}

.notificaciones-item:hover {
  background: #eaf4ff;
}

.notificaciones-icono {
  display: grid;
  flex: 0 0 36px;
  place-items: center;
  height: 36px;
  border-radius: 9px;
  background: #e3f0ff;
  color: #007bff;
  font-size: 17px;
}

.notificaciones-contenido {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 5px;
  min-width: 0;
  overflow-wrap: anywhere;
  font-size: 12px;
}

.notificaciones-contenido strong {
  font-size: 13px;
}

.notificaciones-contenido small {
  color: #64748b;
  font-size: 11px;
}

.notificaciones-estado {
  color: #006bc9;
  font-size: 10px;
  font-weight: 600;
}

.notificaciones-punto {
  flex: 0 0 8px;
  height: 8px;
  margin-top: 6px;
  border-radius: 50%;
  background: #007bff;
}

.notificaciones-vacio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 32px 16px;
  color: #64748b;
  font-size: 13px;
}

.notificaciones-vacio i {
  font-size: 28px;
}

.notificaciones-error {
  padding: 12px 16px;
  color: #b42318;
  font-size: 12px;
}

.notificaciones-pie {
  padding: 12px;
  border-top: 1px solid #e9ecef;
  color: #64748b;
  font-size: 12px;
  text-align: center;
}
</style>