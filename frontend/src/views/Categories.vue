<template>
  <div class="container">
    <h1>Categorías</h1>
    <p class="error" v-if="error">{{ error }}</p>
    <p class="success" v-if="success">{{ success }}</p>

    <div class="card">
      <input v-model="form.name" placeholder="Nombre categoría" />
      <input v-model="form.description" placeholder="Descripción" />
      <button @click="save">Guardar</button>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="c in categories" :key="c.id">
          <td>{{ c.id }}</td>
          <td>{{ c.name }}</td>
          <td>{{ c.status }}</td>
          <td>
            <button @click="edit(c)">Editar</button>
            <button @click="remove(c.id)">Eliminar</button>
          </td>
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
      categories: [],
      form: { id: null, name: '', description: '', status: 1 },
      error: '',
      success: ''
    }
  },
  mounted() {
    this.load()
  },
  methods: {
    load() {
      axios.get((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/categories', {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      }).then(res => {
        this.categories = res.data.categories
      }).catch(() => this.error = 'Error al cargar categorías')
    },
    edit(c) {
      this.form = c
    },
    save() {
      if (!this.form.name) {
        this.error = 'Nombre obligatorio'
        return
      }
      const url = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/categories' + (this.form.id ? '/' + this.form.id : '')
      const method = this.form.id ? 'put' : 'post'
      axios({ method, url, data: this.form, headers: { Authorization: 'Bearer ' + localStorage.getItem('token') } })
        .then(() => {
          this.success = 'Guardado'
          this.form = { id: null, name: '', description: '', status: 1 }
          this.load()
        }).catch(err => {
          this.error = err.response ? JSON.stringify(err.response.data) : 'Error'
        })
    },
    remove(id) {
      axios.delete((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/categories/' + id, {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      }).then(() => this.load())
    }
  }
}
</script>
