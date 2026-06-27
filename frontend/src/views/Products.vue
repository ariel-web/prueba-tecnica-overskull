<template>
  <div class="container">
    <h1>Productos</h1>
    <router-link to="/products/new">Nuevo producto</router-link>

    <div class="card">
      <input v-model="q" placeholder="Buscar producto" />
      <select v-model="category_id">
        <option value="">Todas las categorías</option>
        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <button @click="loadProducts">Buscar</button>
    </div>

    <p v-if="loading">Cargando productos...</p>
    <p class="error" v-if="error">{{ error }}</p>
    <p class="success" v-if="success">{{ success }}</p>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in products" :key="p.id">
          <td>{{ p.id }}</td>
          <td>{{ p.name }}</td>
          <td>{{ p.category ? p.category.name : '-' }}</td>
          <td>{{ p.price }}</td>
          <td>{{ p.stock }}</td>
          <td>
            <router-link :to="'/products/' + p.id + '/edit'">Editar</router-link>
            <router-link :to="'/products/' + p.id + '/stock'">Stock</router-link>
            <button @click="remove(p.id)">Eliminar</button>
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
      products: [],
      categories: [],
      loading: false,
      error: '',
      success: '',
      q: '',
      category_id: ''
    }
  },
  mounted() {
    this.loadCategories()
    this.loadProducts()
  },
  methods: {
    loadCategories() {
      axios.get((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/categories', {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      }).then(res => {
        this.categories = res.data.categories
      })
    },
    loadProducts() {
      this.loading = true
      this.error = ''
      axios.get((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/products?q=' + this.q + '&category_id=' + this.category_id, {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      }).then(res => {
        // Legacy issue: assumes backend returns array directly.
        this.products = res.data
      }).catch(err => {
        this.error = err.response ? 'Error: ' + err.response.status : 'Error de red'
      }).finally(() => {
        this.loading = false
      })
    },
    remove(id) {
      if (!confirm('¿Eliminar producto?')) return
      axios.delete((import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api') + '/products/' + id, {
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      }).then(() => {
        this.success = 'Producto eliminado'
        this.loadProducts()
      }).catch(() => {
        this.error = 'No se pudo eliminar'
      })
    }
  }
}
</script>
