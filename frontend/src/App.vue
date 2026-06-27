<template>
  <div>
    <div class="nav">
      <router-link to="/dashboard">Dashboard</router-link>
      <router-link to="/products">Productos</router-link>
      <router-link to="/categories">Categorías</router-link>
      <button @click="logout">Salir</button>
    </div>
    <router-view />
  </div>
</template>

<script>
export default {
  methods: {
    logout() {
      localStorage.removeItem('token')
      this.$router.push('/login')
    }
  }
}
</script>
