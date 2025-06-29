<template>
  <div class="min-h-screen flex flex-col">

    <!-- Topbar -->
    <nav class="flex items-center justify-between bg-white shadow-md px-6 py-4 relative">
      <!-- Bagian Kiri -->
      <div class="flex items-center space-x-6">
        <!-- Hamburger button muncul hanya mobile -->
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="text-gray-600 hover:text-gray-800 md:hidden"
          aria-label="Toggle sidebar"
        >
          <svg v-if="!sidebarOpen" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
          <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

        <a href="#" class="text-gray-700 hover:text-blue-600">FAQ</a>
        <a href="#" class="text-gray-700 hover:text-blue-600">Panduan</a>
      </div>

      <!-- Bagian Kanan (Notif, Dark Mode, Profil) -->
      <div class="flex items-center space-x-4 relative">
        <!-- Notifikasi -->
        <button @click="toggleDropdown" class="relative text-gray-600 hover:text-gray-800">
          🔔
          <span v-if="notifCount > 0" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-1">
            {{ notifCount }}
          </span>
        </button>

        <!-- Dropdown Notifikasi -->
      <div
          v-if="showDropdown"
          class="absolute right-0 top-12 bg-white shadow-lg rounded-md w-72 p-4 z-50"
        >
          <div class="flex justify-between items-center mb-2">
            <h3 class="font-semibold text-gray-700">Notifikasi</h3>
            <button @click="showDropdown = false" class="text-gray-600 hover:text-red-500 font-bold">
              ✖
            </button>
          </div>

          <ul class="max-h-60 overflow-y-auto divide-y divide-gray-200">
            <li
              v-if="notifications.length === 0"
              class="text-gray-500 text-sm py-2 text-center"
            >
              Tidak ada notifikasi.
            </li>
            <li
              v-for="notif in notifications"
              :key="notif.id"
              class="py-2 text-gray-700"
            >
              <div class="text-sm font-medium">{{ notif.data.message }}</div>
              <div class="text-xs text-gray-400">
                {{ new Date(notif.created_at).toLocaleString('id-ID') }}
              </div>
            </li>
          </ul>

          <button
            @click="clearNotifications"
            class="mt-3 text-blue-600 hover:underline text-sm w-full text-center"
          >
            Hapus Semua Notifikasi
          </button>
        </div>


        <!-- Toggle Dark Mode -->
        <button @click="toggleDarkMode" class="text-gray-600 hover:text-gray-800">
          {{ isDarkMode ? '🌙' : '☀️' }}
        </button>

        <!-- Profil -->
        <div class="relative">
          <button @click="toggleProfileDropdown" class="flex items-center space-x-2">
            <span class="text-gray-700 font-semibold">{{ userName || 'Memuat...' }}</span>
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

    <div class="flex flex-1 overflow-hidden">
      <!-- Sidebar Desktop -->
      <aside class="hidden md:flex flex-col w-64 bg-white p-6 shadow-md">
        <!-- Logo -->
        <img :src="logo" alt="PCU Logo" class="w-32 h-auto mb-4" />
        <!-- Menu -->
        <ul class="space-y-2 flex-1">
          <li v-for="item in filteredMenuItems" :key="item.name">
            <router-link
              :to="getMenuItemRoute(item)"
              class="flex items-center p-3 hover:bg-gray-200 rounded text-center"
              active-class="bg-gray-300 font-semibold"
            >
              <component :is="item.icon" class="w-5 h-5 mr-2" />
              {{ item.name }}
            </router-link>
          </li>
        </ul>
      </aside>

      <!-- Sidebar Drawer Mobile -->
      <transition name="slide">
        <aside
          v-if="sidebarOpen"
          class="fixed inset-y-0 left-0 z-50 w-64 bg-white p-6 shadow-md flex flex-col md:hidden"
        >
          <!-- Logo -->
          <img :src="logo" alt="PCU Logo" class="w-32 h-auto mb-4" />
          <!-- Menu -->
          <ul class="space-y-2 flex-1">
            <li v-for="item in filteredMenuItems" :key="item.name">
              <router-link
                :to="getMenuItemRoute(item)"
                @click.native="sidebarOpen = false"
                class="flex items-center p-3 hover:bg-gray-200 rounded text-center"
                active-class="bg-gray-300 font-semibold"
              >
                <component :is="item.icon" class="w-5 h-5 mr-2" />
                {{ item.name }}
              </router-link>
            </li>
          </ul>
        </aside>
      </transition>

      <!-- Backdrop -->
      <transition name="fade">
        <div
          v-if="sidebarOpen"
          @click="sidebarOpen = false"
          class="fixed inset-0 bg-black/50 z-40 md:hidden"
        ></div>
      </transition>

      <!-- Main Content -->
      <main class="flex-1 overflow-auto p-4 bg-gray-50">
        <slot />
      </main>
    </div>
  </div>
</template>

<script>
import {
  ClipboardDocumentListIcon,
  ClockIcon,
  ArrowUturnLeftIcon,
  ArchiveBoxIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline';
import { mapGetters } from 'vuex';
import logo from '@/assets/logo-pcu.png';

export default {
  name: 'ResponsiveLayout',
  data() {
    return {
      logo,
      sidebarOpen: false,
      notifCount: 0,
      notifications: [],
      showDropdown: false,
      showProfileDropdown: false,
      isDarkMode: false,
    };
  },
  computed: {
    ...mapGetters(['userName', 'userRole']),
    filteredMenuItems() {
      if (!this.userRole) return [];

      if (this.userRole === 'PTIK') return this.allMenuItems;

      return this.allMenuItems.filter((item) =>
        ['Request', 'Loan', 'Return'].includes(item.name)
      );
    },
    allMenuItems() {
      return [
        { name: 'Request', icon: ClipboardDocumentListIcon },
        { name: 'Loan', icon: ClockIcon },
        { name: 'Return', icon: ArrowUturnLeftIcon },
        { name: 'Item', icon: ArchiveBoxIcon },
        { name: 'Prediction', icon: ChartBarIcon },
      ];
    },
  },
  mounted() {
    if (!this.userRole) {
      this.$store.dispatch('fetchUserData');
      this.fetchNotifications();
    }
  },
  methods: {
    toggleDropdown() {
      this.showDropdown = !this.showDropdown;
      if (this.showDropdown) this.showProfileDropdown = false;
    },
    toggleProfileDropdown() {
      this.showProfileDropdown = !this.showProfileDropdown;
      if (this.showProfileDropdown) this.showDropdown = false;
    },
    async fetchNotifications() {
      try {
        const response = await axios.get('/notifications');
        this.notifications = response.data;
        this.notifCount = this.notifications.length;
      } catch (error) {
        console.error('Gagal mengambil notifikasi:', error);
      }
    },
    async clearNotifications() {
      try {
        await axios.delete('/notifications');
        this.notifications = [];
      } catch (error) {
        console.error('Gagal menghapus notifikasi:', error);
      }
    },
    toggleDarkMode() {
      this.isDarkMode = !this.isDarkMode;
      document.documentElement.classList.toggle('dark');
    },
    logout() {
      this.$axios
        .post('/api/logout', {}, { withCredentials: true })
        .then(() => {
          this.user = null;
          this.$router.push('/login');
        })
        .catch((error) => {
          console.error('Logout gagal:', error);
        });
    },
    getMenuItemRoute(item) {
      return `/${item.name.toLowerCase()}`;
    },
  },
};
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(-100%);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
