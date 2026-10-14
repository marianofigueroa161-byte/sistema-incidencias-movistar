import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import IncidenciasView from '../views/IncidenciasView.vue'
import UsuariosView from '../views/UsuariosView.vue'
import TecnicosView from '../views/TecnicosView.vue'
import ReportesView from '../views/ReportesView.vue'
import ConfiguracionView from '../views/ConfiguracionView.vue'
const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: LoginView, meta: { soloInvitados: true } },
  { path: '/dashboard', name: 'dashboard', component: DashboardView, meta: { requiereAutenticacion: true } },
  { path: '/incidencias', name: 'incidencias', component: IncidenciasView, meta: { requiereAutenticacion: true } },
  { path: '/usuarios', name: 'usuarios', component: UsuariosView, meta: { requiereAutenticacion: true } },
  { path: '/tecnicos', name: 'tecnicos', component: TecnicosView, meta: { requiereAutenticacion: true } },
  { path: '/reportes', name: 'reportes', component: ReportesView, meta: { requiereAutenticacion: true } },
  { path: '/configuracion', name: 'configuracion', component: ConfiguracionView, meta: { requiereAutenticacion: true } },
  { path: '/:pathMatch(.*)*', redirect: '/login' },
]
const router = createRouter({
  history: createWebHistory(),
  routes,
})
router.beforeEach((to) => {
  const usuarioGuardado = localStorage.getItem('usuario') || sessionStorage.getItem('usuario')
  const autenticado = Boolean(usuarioGuardado)
  if (to.meta.requiereAutenticacion && !autenticado) return '/login'
  if (to.meta.soloInvitados && autenticado) return '/dashboard'
  return true
})
export default router