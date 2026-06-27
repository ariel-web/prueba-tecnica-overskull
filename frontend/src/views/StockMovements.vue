<template>
  <div class="container">
    <h1>Movimientos de Stock</h1>
    <router-link to="/products">Volver</router-link>

    <p class="error" v-if="error">{{ error }}</p>
    <p class="success" v-if="success">{{ success }}</p>

    <div class="card">
      <select v-model="form.type">
        <option value="entrada">Entrada</option>
        <option value="salida">Salida</option>
      </select>
      <input v-model="form.quantity" placeholder="Cantidad" />
      <input v-model="form.reason" placeholder="Motivo" />
      <button @click="save">Registrar</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tipo</th>
          <th>Cantidad</th>
          <th>Motivo</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in movements" :key="m.id">
          <td>{{ m.id }}</td>
          <td>{{ m.type }}</td>
          <td>{{ m.quantity }}</td>
          <td>{{ m.reason }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  data() {
    return {
      movements: [],
      form: { type: 'entrada', quantity: '', reason: '' },
      error: '',
      success: ''
    }
  },
  mounted() {
    this.load()
  },
  methods: {
    load() {
      axios.get((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/products/' + this.$route.params.id + '/stock-movements', {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      }).then(res => this.movements = res.data)
    },
    save() {
      // Legacy issue: frontend does not validate stock or numeric quantity properly.
      axios.post((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/products/' + this.$route.params.id + '/stock-movements', this.form, {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      }).then(() => {
        this.success = 'Movimiento registrado'
        this.load()
      }).catch(err => {
        this.error = err.response ? JSON.stringify(err.response.data) : 'Error de red'
      })
    }
  }
}
</script>
