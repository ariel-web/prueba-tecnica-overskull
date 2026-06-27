<template>
  <div class="container">
    <h1>Dashboard</h1>
    <p v-if="loading">Cargando...</p>
    <p class="error" v-if="error">{{ error }}</p>

    <div class="card">
      <h3>Productos: {{ data.products }}</h3>
      <h3>Categorías: {{ data.categories }}</h3>
      <h3>Bajo stock: {{ data.low_stock ? data.low_stock.length : 0 }}</h3>
    </div>

    <div class="card">
      <h3>Últimos movimientos</h3>
      <ul>
        <li v-for="m in data.last_movements" :key="m.id">
          {{ m.type }} - {{ m.quantity }} - {{ m.reason }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      loading: false,
      error: '',
      data: {}
    }
  },
  mounted() {
    this.loading = true
    axios.get((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/dashboard', {
      headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
    }).then(res => {
      this.data = res.data
    }).catch(() => {
      this.error = 'No se pudo cargar dashboard'
    }).finally(() => {
      this.loading = false
    })
  }
}
</script>
