<template>
  <div class="container">
    <div class="card">
      <h2>Login Legacy</h2>
      <p class="error" v-if="error">{{ error }}</p>
      <input v-model="email" placeholder="Email" />
      <input v-model="password" placeholder="Password" type="password" />
      <button @click="login">Ingresar</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      email: 'admin@legacy.test',
      password: 'password',
      error: ''
    }
  },
  methods: {
    login() {
      // Legacy issue: no loading state and no strong frontend validation.
      axios.post((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/login', {
        email: this.email,
        password: this.password
      }).then(res => {
        localStorage.setItem('token', res.data.token)
        this.$router.push('/dashboard')
      }).catch(err => {
        this.error = err.response ? err.response.data.message || err.response.data.error : 'Error de red'
      })
    }
  }
}
</script>
