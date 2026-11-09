import { createRouter, createWebHistory } from 'vue-router'
import api from '../services/api'
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
router.beforeEach(async (to) => {
  try {
    const respuesta = await api.get('/sesion')
    const rol = respuesta.data?.usuario?.rol
    if (to.meta.soloInvitados) return '/dashboard'
    if (['usuarios', 'tecnicos'].includes(to.name) && !['ADMINISTRADOR', 'SUPERVISOR'].includes(rol)) return '/incidencias'
    if (['reportes'].includes(to.name) && !['ADMINISTRADOR', 'SUPERVISOR'].includes(rol)) return '/incidencias'
    return true
  } catch {
    if (to.meta.requiereAutenticacion) return '/login'
    return true
  }
})
export default router
