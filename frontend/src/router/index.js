import { createRouter, createWebHistory } from 'vue-router'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import IncidenciasView from '../views/IncidenciasView.vue'
import UsuariosView from '../views/UsuariosView.vue'
import TecnicosView from '../views/TecnicosView.vue'
import ReportesView from '../views/ReportesView.vue'
const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: LoginView, meta: { soloInvitados: true } },
  { path: '/dashboard', name: 'dashboard', component: DashboardView, meta: { requiereAutenticacion: true } },
  { path: '/incidencias', name: 'incidencias', component: IncidenciasView, meta: { requiereAutenticacion: true } },
  { path: '/usuarios', name: 'usuarios', component: UsuariosView, meta: { requiereAutenticacion: true } },
  { path: '/tecnicos', name: 'tecnicos', component: TecnicosView, meta: { requiereAutenticacion: true } },
  { path: '/reportes', name: 'reportes', component: ReportesView, meta: { requiereAutenticacion: true } },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]
const router = createRouter({
  history: createWebHistory(),
  routes,
})
router.beforeEach(async (to, from) => {
  const auth = useAuthStore()

  if (auth.cerrandoSesion) {
    if (auth.salidaConfirmada && to.name === 'login') {
      auth.salidaConfirmada = false
      return true
    }
    return false
  }

  if (auth.ingresoConfirmado) {
    auth.ingresoConfirmado = false

    if (from.name === 'login' && to.name === 'dashboard' && auth.autenticado) {
      return true
    }
  }
  try {
    const respuesta = await api.get('/sesion')
    const authStore = useAuthStore()
    authStore.usuario = respuesta.data?.usuario ?? null
    const rol = authStore.rol
    if (to.meta.soloInvitados) return '/dashboard'
    if (['usuarios', 'tecnicos'].includes(to.name) && !['ADMINISTRADOR', 'SUPERVISOR'].includes(rol)) return '/incidencias'
    if (['reportes'].includes(to.name) && !['ADMINISTRADOR', 'SUPERVISOR'].includes(rol)) return '/incidencias'
    return true
  } catch {
    useAuthStore().usuario = null
    if (to.meta.requiereAutenticacion) return '/login'
    return true
  }
})
export default router
