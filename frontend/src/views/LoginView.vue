<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
const router = useRouter()
const authStore = useAuthStore()
const usuario = ref('')
const clave = ref('')
const mostrarClave = ref(false)
const mensaje = ref('')
const iniciarSesion = async () => {
  mensaje.value = ''
  if (!usuario.value || !clave.value) {
    mensaje.value = 'Ingrese su usuario y contraseña.'
    return
  }
  try {
    await authStore.iniciarSesion(
      usuario.value,
      clave.value,
      false
    )
    await router.push('/dashboard')
  } catch (error) {
    mensaje.value =
      error.response?.data?.message ||
      'No fue posible conectar con el servidor.'
  }
}
</script>
<template>
  <main class="login-page" translate="no">
    <section class="information-panel">
      <div class="brand">
        <div class="brand-icon">M</div>
        <div>
          <strong>Movistar</strong>
        </div>
      </div>
    </section>
    <section class="form-panel">
      <div class="login-card">
        <div class="mobile-brand">
          <div class="brand-icon">M</div>
          <strong>Movistar</strong>
        </div>
        <div class="form-header">
          <h2>Iniciar sesión</h2>
        </div>
        <form @submit.prevent="iniciarSesion">
          <div class="form-group">
            <label for="usuario">
              Correo electrónico o documento
            </label>
            <div class="input-container">
              <i class="bi bi-person"></i>
              <input
                id="usuario"
                v-model.trim="usuario"
                type="text"
                placeholder="Ingrese su usuario"
                autocomplete="username"
                :disabled="authStore.cargando"
              />
            </div>
          </div>
          <div class="form-group">
            <label for="clave">Contraseña</label>
            <div class="input-container">
              <i class="bi bi-lock"></i>
              <input
                id="clave"
                v-model="clave"
                :type="mostrarClave ? 'text' : 'password'"
                placeholder="Ingrese su contraseña"
                autocomplete="current-password"
                :disabled="authStore.cargando"
              />
              <button
                class="show-password"
                type="button"
                :aria-label="
                  mostrarClave
                    ? 'Ocultar contraseña'
                    : 'Mostrar contraseña'
                "
                :disabled="authStore.cargando"
                @click="mostrarClave = !mostrarClave"
              >
                <i
                  :class="
                    mostrarClave
                      ? 'bi bi-eye-slash'
                      : 'bi bi-eye'
                  "
                ></i>
              </button>
            </div>
          </div>

          <p v-if="mensaje" class="message">
            <i class="bi bi-info-circle"></i>
            {{ mensaje }}
          </p>
          <button
            class="login-button"
            type="submit"
            :disabled="authStore.cargando"
          >
            <span
              v-if="authStore.cargando"
              class="button-content"
            >
              <span
                class="spinner-border spinner-border-sm"
                aria-hidden="true"
              ></span>
              Validando...
            </span>
            <span v-else class="button-content">
              Ingresar
              <i class="bi bi-arrow-right"></i>
            </span>
          </button>
        </form>
        <div class="help">
          <i class="bi bi-headset"></i>
          <div>
            <strong>¿Necesitas ayuda?</strong>
            <span>Comunícate con el área de soporte.</span>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
<style scoped>
* {
  box-sizing: border-box;
}
.login-page {
  display: grid;
  grid-template-columns: minmax(420px, 46%) 1fr;
  min-height: 100vh;
  color: #172b4d;
  background: #f5f8fb;
  font-family: Arial, Helvetica, sans-serif;
}
.information-panel {
  position: relative;
  display: flex;
  overflow: hidden;
  flex-direction: column;
  justify-content: flex-start;
  padding: 44px 58px;
  color: white;
  background:
    radial-gradient(
      circle at 15% 15%,
      rgba(255, 255, 255, 0.2),
      transparent 25%
    ),
    linear-gradient(145deg, #019be1, #0066a7);
}
.information-panel::after {
  position: absolute;
  right: -130px;
  bottom: -140px;
  width: 390px;
  height: 390px;
  content: '';
  border: 55px solid rgba(255, 255, 255, 0.08);
  border-radius: 50%;
}
.brand,
.mobile-brand {
  display: flex;
  align-items: center;
  gap: 13px;
}
.brand-icon {
  display: grid;
  width: 48px;
  height: 48px;
  place-items: center;
  border-radius: 16px;
  color: #019be1;
  background: white;
  font-size: 27px;
  font-weight: 800;
}
.brand strong {
  display: block;
  font-size: 24px;
}
.information-content {
  position: relative;
  z-index: 1;
  max-width: 560px;
  margin: auto 0;
  transform: none;
}
.tag {
  display: inline-block;
  margin-bottom: 30px;
  padding: 8px 14px;
  border: 1px solid rgba(255, 255, 255, 0.35);
  border-radius: 30px;
  background: rgba(255, 255, 255, 0.12);
  font-size: 13px;
  font-weight: 700;
}
.information-content ul {
  display: grid;
  gap: 16px;
  padding: 0;
  list-style: none;
}
.information-content li {
  display: flex;
  align-items: center;
  gap: 13px;
  font-size: 15px;
}
.information-content li i {
  display: grid;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  place-items: center;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.16);
}
.form-panel {
  display: grid;
  padding: 40px;
  place-items: center;
}
.login-card {
  width: min(100%, 460px);
  padding: 30px;
  border: 1px solid #e5ebf1;
  border-radius: 24px;
  background: white;
  box-shadow: 0 22px 60px rgba(18, 52, 77, 0.11);
}
.mobile-brand {
  display: none;
}
.form-header h2 {
  text-align: center;
  margin: 0 0 22px;
  font-size: 32px;
}
.form-group {
  margin-bottom: 16px;
}
.form-group label {
  display: block;
  margin-bottom: 8px;
  color: #263b50;
  font-size: 14px;
  font-weight: 700;
}
.input-container {
  position: relative;
  display: flex;
  align-items: center;
}
.input-container > i {
  position: absolute;
  z-index: 1;
  left: 15px;
  color: #7a8b9b;
}
.input-container input {
  width: 100%;
  height: 52px;
  padding: 0 48px 0 44px;
  border: 1px solid #d8e1e9;
  border-radius: 12px;
  outline: none;
  color: #172b4d;
  background: #fbfcfd;
  font-size: 14px;
  transition: 0.2s;
}
.input-container input:focus {
  border-color: #019be1;
  background: white;
  box-shadow: 0 0 0 4px rgba(1, 155, 225, 0.1);
}
.input-container input:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}
.show-password {
  position: absolute;
  right: 10px;
  padding: 9px;
  border: 0;
  color: #607285;
  background: transparent;
  cursor: pointer;
}
.show-password:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.message {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 16px;
  padding: 11px 13px;
  border-radius: 9px;
  color: #805b00;
  background: #fff5d7;
  font-size: 13px;
}
.login-button {
  display: flex;
  width: 100%;
  height: 52px;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 12px;
  color: white;
  background: linear-gradient(90deg, #019be1, #0073b7);
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 12px 25px rgba(1, 137, 200, 0.25);
  transition:
    filter 0.2s,
    transform 0.2s,
    opacity 0.2s;
}
.login-button:hover:not(:disabled) {
  filter: brightness(1.05);
  transform: none;
}
.login-button:disabled {
  cursor: not-allowed;
  opacity: 0.7;
  filter: none;
  transform: none;
}
.button-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.help {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #edf1f4;
  color: #607285;
}
.help > i {
  color: #019be1;
  font-size: 23px;
  flex-shrink: 0;
}
.help > div {
  display: flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}
.help strong,
.help span {
  display: inline;
  font-size: 12px;
}
.help strong {
  margin-bottom: 0;
  color: #35495c;
}
@media (max-width: 900px) {
  .login-page {
    display: block;
  }
  .information-panel {
    display: none;
  }
  .form-panel {
    min-height: 100vh;
    padding: 24px;
  }
  .mobile-brand {
    display: flex;
    margin-bottom: 32px;
  }
  .mobile-brand strong {
    font-size: 22px;
  }
}
@media (max-width: 520px) {
  .login-card {
    padding: 30px 22px;
    border: 0;
    box-shadow: none;
  }
  .form-options {
    align-items: flex-start;
    flex-direction: column;
    gap: 12px;
  }
}
</style>