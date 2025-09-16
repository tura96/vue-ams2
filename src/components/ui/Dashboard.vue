<template>
  <div class="dashboard">
    <header>
      <h1>Welcome, {{ authStore.user?.display_name }}</h1>
      <button v-if="authStore.isLoggedIn" @click="handleLogout">Logout</button>
    </header>

    <div class="posts-section">
      <h2>Recent Posts</h2>
      <div v-if="loading">Loading posts...</div>
      <div v-else>
        <div v-for="post in posts" :key="post.id" class="post-item">
          <h3>{{ post.title }}</h3>
          <dl>
            <dt>Asset Tag</dt>
            <dd>{{ post.asset_tag }}</dd>

            <dt>Assigned To</dt>
            <dd>{{ post.assigned_to || 'Not assigned' }}</dd>

            <dt>Date Created</dt>
            <dd>{{ post.date_created }}</dd>

            <dt>Date Modified</dt>
            <dd>{{ post.date_modified }}</dd>

            <dt>Description</dt>
            <dd v-html="'No description'"></dd>

            <dt>ID</dt>
            <dd>{{ post.id }}</dd>

            <dt>Location</dt>
            <dd>{{ post.location || 'Not specified' }}</dd>

            <dt>Model</dt>
            <dd>{{ post.model }}</dd>

            <dt>Notes</dt>
            <dd>{{ post.notes || 'No notes' }}</dd>

            <dt>Purchase Cost</dt>
            <dd>{{ post.purchase_cost ? `$${post.purchase_cost}` : 'Not specified' }}</dd>

            <dt>Purchase Date</dt>
            <dd>{{ post.purchase_date || 'Not specified' }}</dd>

            <dt>RFID Tag</dt>
            <dd>{{ post.rfid_tag || 'Not specified' }}</dd>

            <dt>Serial Number</dt>
            <dd>{{ post.serial_number }}</dd>

            <dt>Status</dt>
            <dd>{{ post.status }}</dd>

            <dt>Warranty Expiry</dt>
            <dd>{{ post.warranty_expiry || 'Not specified' }}</dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { amsApi } from '@/services/amsApi.ts'


const router = useRouter()
const authStore = useAuthStore()
const posts = ref([])
const loading = ref(false)

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}

const fetchPosts = async () => {
  loading.value = true
  try {
    const result = await amsApi.getAssets({per_page:1})
    posts.value = result.data || []
  } catch (error) {
    console.error('Failed to fetch posts:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchPosts()
})
</script>

<style scoped>
.dashboard {
  max-width: 800px;
  margin: 0 auto;
  padding: 2rem;
}

header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}

.post-item {
  margin-bottom: 2rem;
  padding: 1rem;
  border: 1px solid #eee;
  border-radius: 4px;
}

.post-item {
  margin-bottom: 2rem;
  padding: 1rem;
  border: 1px solid #eee;
  border-radius: 4px;
}

.post-item h3 {
  margin-top: 0;
  color: #333;
}

dl {
  display: grid;
  grid-template-columns: max-content 1fr;
  gap: 0.5rem 1rem;
  margin: 1rem 0;
}

dt {
  font-weight: bold;
  color: #555;
}

dd {
  margin: 0;
  color: #333;
}

dd:empty::after {
  content: 'Not specified';
  color: #999;
}
</style>