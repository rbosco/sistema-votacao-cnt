<template>
  <div class="admin-header">
    <div class="govbr-header">
      <div class="container">
        <div class="header-content">
          <div class="logo">
            <img src="/images/logo-cnt.png" alt="Logo CNT" class="logo-cnt" />
            <h1>Sistema de Votação CNT</h1>
          </div>
          <div class="user-info">
            <span class="user-name">{{ user?.name }}</span>
            <button @click="handleLogout" class="btn btn-outline btn-sm">Sair</button>
          </div>
        </div>
      </div>
    </div>

    <div class="admin-nav">
      <div class="container">
        <nav>
          <router-link to="/admin" class="nav-link" exact-active-class="active">Dashboard</router-link>
          <router-link to="/admin/proposals" class="nav-link" active-class="active">Propostas</router-link>
          <router-link to="/admin/votes" class="nav-link" active-class="active">Votos</router-link>
          <router-link to="/admin/users" class="nav-link" active-class="active">Usuários</router-link>
          <a href="/" class="nav-link">Página de Votação</a>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const user = computed(() => authStore.user);

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<style scoped>
.admin-header {
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-3);
  color: var(--white);
}

.user-name {
  font-weight: 500;
}

.btn-sm {
  padding: var(--spacing-2) var(--spacing-3);
  font-size: 0.875rem;
}

.btn-outline {
  border-color: var(--white);
  color: var(--white);
}

.btn-outline:hover {
  background-color: var(--white);
  color: var(--blue-warm-vivid-60);
}

.admin-nav {
  background-color: var(--blue-warm-vivid-50);
  box-shadow: var(--shadow-md);
}

.admin-nav nav {
  display: flex;
  gap: var(--spacing-2);
  padding: var(--spacing-2) 0;
}

.nav-link {
  color: var(--white);
  text-decoration: none;
  padding: var(--spacing-2) var(--spacing-4);
  border-radius: var(--border-radius-sm);
  font-weight: 500;
  transition: background-color 0.2s;
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
  background-color: var(--blue-warm-vivid-70);
}

.logo-cnt {
  max-height: 50px;
  height: auto;
}
</style>
