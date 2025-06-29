<template>
  <nav class="flex items-center justify-between bg-white shadow-md px-6 py-4 relative">
    <!-- Bagian Kiri -->
    <div class="flex items-center space-x-6">
    </div>

    <!-- Bagian Kanan (Notifikasi, Dark Mode, Akun) -->
    <div class="flex items-center space-x-4 relative">
      <!-- Tombol Notifikasi -->
      <button @click="toggleDropdown" class="relative text-gray-600 hover:text-gray-800">
        🔔
        <span v-if="notifCount > 0" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-1">
          {{ notifCount }}
        </span>
      </button>

      <!-- Dropdown Notifikasi -->
      <div v-if="showDropdown" class="absolute right-0 top-12 bg-white shadow-lg rounded-md w-64 p-4 z-50">
        <div class="flex justify-between items-center mb-2">
          <h3 class="font-semibold text-gray-700">Notifikasi</h3>
          <button @click="showDropdown = false" class="text-gray-600 hover:text-red-500 font-bold">✖</button>
        </div>
        <ul class="max-h-40 overflow-y-auto">
          <li
            v-for="notif in notifications"
            :key="notif.id"
            class="p-2 border-b text-gray-600"
          >
            {{ notif.data.message }}
          </li>
        </ul>
        <button
          @click="clearNotifications"
          class="mt-2 text-blue-600 hover:underline text-sm"
        >
          Hapus Notifikasi
        </button>
      </div>

      <!-- Informasi Akun dengan Dropdown Profil -->
      <div class="relative">
        <button @click="toggleProfileDropdown" class="flex items-center space-x-2">
          <span class="text-gray-700 font-semibold">
            {{ userName || 'Memuat...' }}  <!-- Menampilkan nama pengguna -->
          </span>
        </button>

        <!-- Dropdown Profil -->
        <div v-if="showProfileDropdown" class="absolute right-0 top-16 bg-white shadow-lg rounded-md w-64 p-4 z-40">
          <div class="flex justify-between items-center mb-2">
            <h3 class="font-semibold text-gray-700">Akun Saya</h3>
            <button @click="showProfileDropdown = false" class="text-gray-600 hover:text-red-500 font-bold">✖</button>
          </div>
          <ul>
            <li class="p-2 border-b text-gray-600 hover:bg-gray-200 cursor-pointer">Profil</li>
            <li @click="logout" class="p-2 text-gray-600 hover:bg-gray-200 cursor-pointer">Logout</li>
          </ul>
        </div>
      </div>
    </div>
  </nav>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  data() {
    return {
      notifications: [],
      notifCount: 0,
      showDropdown: false,
      showProfileDropdown: false,
      isDarkMode: false,
    };
  },
  computed: {
    ...mapGetters(['userName']), // dari Vuex
  },
  mounted() {
    this.$store.dispatch('fetchUserData');
    this.fetchNotifications();
    this.pollingInterval = setInterval(() => {
      this.fetchNotifications();
    }, 10000); // 10 detik
  },
  beforeUnmount() {
    // Bersihkan interval saat komponen di-unmount
    clearInterval(this.pollingInterval);
  },
  methods: {
    toggleDropdown() {
      this.showDropdown = !this.showDropdown;
      if (this.showDropdown) {
        this.showProfileDropdown = false;
        this.fetchNotifications(); // Segarkan notifikasi saat dibuka
      }
    },
    toggleProfileDropdown() {
      this.showProfileDropdown = !this.showProfileDropdown;
      if (this.showProfileDropdown) {
        this.showDropdown = false;
      }
    },
    async fetchNotifications() {
      try {
        const response = await this.$axios.get('/api/notifications', { withCredentials: true });
        this.notifications = response.data;
        this.notifCount = this.notifications.length;
      } catch (error) {
        console.error('Gagal mengambil notifikasi:', error);
      }
    },
    async clearNotifications() {
      try {
        await this.$axios.delete('/api/notifications', { withCredentials: true });
        this.notifications = [];
        this.notifCount = 0;
      } catch (error) {
        console.error('Gagal menghapus notifikasi:', error);
      }
    },
    toggleDarkMode() {
      this.isDarkMode = !this.isDarkMode;
      document.documentElement.classList.toggle('dark');
    },
    async logout() {
      try {
        await this.$axios.post('/api/logout', {}, { withCredentials: true });
        this.$router.push('/login');
      } catch (error) {
        console.error('Logout gagal:', error);
      }
    }
  }
};
</script>
