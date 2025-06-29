<template>
  <div class="p-6 md:p-8 bg-gray-50 min-h-screen">
    <!-- Header Sambutan -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800">Selamat Datang, {{ currentUser?.name || 'Admin' }}!</h1>
      <p class="text-gray-600">Berikut adalah ringkasan aktivitas sistem peminjaman saat ini.</p>
    </div>

    <!-- Indikator Loading -->
    <div v-if="loading" class="text-center py-20">
      <p class="text-gray-500">Memuat data dashboard...</p>
    </div>

    <!-- Konten Dashboard -->
    <div v-else class="space-y-8">
      <!-- Statistik -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <router-link to="/request" class="bg-white p-6 rounded-xl shadow-md border-l-4 border-blue-500 flex items-center justify-between hover:shadow-lg transition">
          <div>
            <p class="text-sm font-medium text-gray-500">Permohonan Baru</p>
            <p class="text-3xl font-bold text-gray-800">{{ stats.pending_requests }}</p>
          </div>
          <div class="bg-blue-100 p-3 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0l-8-4l-8 4" />
            </svg>
          </div>
        </router-link>

        <router-link to="/loan" class="bg-white p-6 rounded-xl shadow-md border-l-4 border-green-500 flex items-center justify-between hover:shadow-lg transition">
          <div>
            <p class="text-sm font-medium text-gray-500">Peminjaman Aktif</p>
            <p class="text-3xl font-bold text-gray-800">{{ stats.active_loans }}</p>
          </div>
          <div class="bg-green-100 p-3 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </div>
        </router-link>

        <router-link to="/return" class="bg-white p-6 rounded-xl shadow-md border-l-4 border-red-500 flex items-center justify-between hover:shadow-lg transition">
          <div>
            <p class="text-sm font-medium text-gray-500">Terlambat Kembali</p>
            <p class="text-3xl font-bold text-gray-800">{{ stats.overdue_loans }}</p>
          </div>
          <div class="bg-red-100 p-3 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </router-link>

        <router-link v-if="stats.total_items !== null" to="/item" class="bg-white p-6 rounded-xl shadow-md border-l-4 border-gray-500 flex items-center justify-between hover:shadow-lg transition">
          <div>
            <p class="text-sm font-medium text-gray-500">Barang Tersedia</p>
            <p class="text-3xl font-bold text-gray-800">{{ stats.available_items }} / {{ stats.total_items }}</p>
          </div>
          <div class="bg-gray-100 p-3 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
          </div>
        </router-link>
      </div>

      <!-- Aktivitas dan Jadwal -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Aktivitas Terbaru -->
        <div>
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Aktivitas Terbaru</h2>
          <div class="bg-white p-4 rounded-xl shadow-md h-full">
            <ul v-if="stats.recent_activities.length > 0" class="divide-y divide-gray-200">
              <li v-for="activity in stats.recent_activities" :key="activity.id" class="py-3 flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900">Permohonan oleh <span class="font-bold">{{ activity.requester.name }}</span> diupdate</p>
                  <p class="text-sm text-gray-500">Status sekarang: <span class="font-semibold capitalize">{{ activity.status.replace('_', ' ') }}</span></p>
                </div>
                <p class="text-sm text-gray-400">{{ formatDate(activity.updated_at) }}</p>
              </li>
            </ul>
            <p v-else class="text-gray-500 p-4 text-center">Tidak ada aktivitas terbaru.</p>
          </div>
        </div>

        <!-- Jadwal Peminjaman -->
        <div>
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Jadwal Peminjaman Mendatang</h2>
          <div class="bg-white p-4 rounded-xl shadow-md h-full">
            <ul v-if="stats.upcoming_loans && stats.upcoming_loans.length > 0" class="divide-y divide-gray-200">
              <li v-for="loan in stats.upcoming_loans" :key="loan.id" class="py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                  <div class="mb-1 sm:mb-0">
                    <p class="text-sm text-gray-800">
                      <span class="font-semibold">{{ loan.item.brand }} ({{ loan.item.code }})</span>
                      akan dipinjam oleh
                      <span class="font-bold text-black">{{ loan.requester.name }}</span>
                    </p>
                  </div>
                  <div class="text-sm font-semibold text-blue-600">
                    {{ formatDateTime(loan.start_at) }}
                  </div>
                </div>
              </li>
            </ul>
            <p v-else class="text-gray-500 p-4 text-center">Tidak ada jadwal peminjaman dalam 7 hari ke depan.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script>
export default {
  name: 'DashboardPage',
  data() {
    return {
      loading: true,
      currentUser: null,
      stats: {
        pending_requests: 0,
        active_loans: 0,
        overdue_loans: 0,
        available_items: 0,
        total_items: 0,
        recent_activities: [],
        upcoming_loans: [],
      },
    };
  },
  methods: {
    async fetchDashboardData() {
      this.loading = true;
      try {
        const response = await this.$axios.get('/api/dashboard/stats');
        this.stats = response.data;
      } catch (error) {
        this.$swal.fire('Gagal!', 'Tidak dapat memuat data dashboard.', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchCurrentUser() {
      try {
        const response = await this.$axios.get('/api/me');
        this.currentUser = response.data;
      } catch (error) {
        console.error("Gagal mengambil data user:", error);
      }
    },
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      const options = { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' };
      return date.toLocaleDateString('id-ID', options);
    },
    formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const options = {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    };
    return date.toLocaleDateString('id-ID', options);
    }
  },
  created() {
    this.fetchCurrentUser();
    this.fetchDashboardData();
  },
};
</script>
