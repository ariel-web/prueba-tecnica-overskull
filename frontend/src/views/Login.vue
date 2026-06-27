<template>
  <div class="h-full flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
      <h2 class="text-2xl font-bold mb-6 text-gray-800">Login</h2>

      <p v-if="auth.error" class="text-red-600 mb-4 text-sm bg-red-50 p-3 rounded">{{ auth.error }}</p>

      <div class="space-y-4">
        <FormField label="Email" :error="validation.get('email')" required>
          <input
            v-model="form.email"
            type="email"
            placeholder="Email"
            autocomplete="username"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            @keyup.enter="handleLogin"
          />
        </FormField>

        <FormField label="Contraseña" :error="validation.get('password')" required>
          <input
            v-model="form.password"
            type="password"
            placeholder="Password"
            autocomplete="current-password"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            @keyup.enter="handleLogin"
          />
        </FormField>

        <button
          :disabled="auth.loading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded disabled:opacity-50"
        >
          <span v-if="auth.loading" class="inline-flex items-center gap-2">
            <span class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></span>
            Ingresando...
          </span>
          <span v-else>Ingresar</span>
        </button>
      </div>

      <div class="mt-4 text-xs text-gray-500 text-center">
        <p>Credenciales de prueba:</p>
        <p>admin@legacy.test / password</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useValidation } from '../composables/useValidation'
import FormField from '../components/FormField.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const validation = useValidation()

const form = reactive({
  email: 'admin@legacy.test',
  password: 'password',
})

function validateForm() {
  return validation.validate(form, {
    email: { required: true, email: true, label: 'Email' },
    password: { required: true, label: 'Contraseña' },
  })
}

async function handleLogin() {
  validation.clear()

  if (!validateForm()) return

  const success = await auth.login(form.email, form.password)
  if (success) {
    const redirect = route.query.redirect || '/dashboard'
    router.push(redirect)
  } else {
    form.password = ''
  }
}
</script>
