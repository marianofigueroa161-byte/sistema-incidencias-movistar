import { defineStore } from 'pinia'
import api from '../services/api'
const usuarioGuardado =
  localStorage.getItem('usuario') ||
  sessionStorage.getItem('usuario')
export const useAuthStore = defineStore('auth', {
  state: () => ({
    usuario: usuarioGuardado
      ? JSON.parse(usuarioGuardado)
      : null,
    cargando: false,
  }),
  getters: {
    autenticado: (state) => state.usuario !== null,
    rol: (state) => state.usuario?.rol ?? null,
  },
  actions: {
    async iniciarSesion(usuario, clave, recordar = false) {
      this.cargando = true
      try {
        const respuesta = await api.post('/login', {
          usuario,
          clave,
        })
        this.usuario = respuesta.data.usuario
        localStorage.removeItem('usuario')
        sessionStorage.removeItem('usuario')
        const almacenamiento = recordar
          ? localStorage
          : sessionStorage
        almacenamiento.setItem(
          'usuario',
          JSON.stringify(this.usuario)
        )
        return respuesta.data
      } finally {
        this.cargando = false
      }
    },
    async cerrarSesion() {
      try {
        await api.post('/logout')
      } finally {
        this.usuario = null
        localStorage.removeItem('usuario')
        sessionStorage.removeItem('usuario')
      }
    },
  },
})