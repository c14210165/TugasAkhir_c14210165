<template>
  <div class="p-6 bg-white shadow-md rounded-md">
    
    <div class="flex items-center justify-between border-b mb-4">
      <div class="flex">
        <button v-for="tab in itemTypeOptions" :key="tab.value" @click="activeTypeTab = tab.value"
                :class="activeTypeTab === tab.value ? 'border-b-4 border-blue-500 text-blue-600' : 'text-gray-600'"
                class="px-6 py-3 hover:text-blue-500 transition capitalize">
          {{ tab.label }}
        </button>
      </div>

      <div class="flex items-center gap-2">
          <label for="month_filter" class="block text-sm font-medium text-gray-700">Pilih Bulan:</label>
          <input type="month" id="month_filter" v-model="filterMonth" class="border-gray-300 rounded-md shadow-sm">
      </div>
    </div>

    <div v-if="loading" class="text-center py-10"><p class="text-lg">Memuat data rekap untuk <span class="font-bold">{{ formattedMonth }}</span>...</p></div>

    <div v-else-if="reportData" class="space-y-8">
      <div>
        <h3 class="text-xl font-semibold mb-2">Rekap per Unit/Jurusan</h3>
        <table class="w-full border border-gray-300 rounded-md text-sm">
          
          <thead>
            <tr class="bg-gray-200 text-gray-700">
              <th class="border p-2 text-left">Nama Unit/Jurusan</th>
              <th class="border p-2 text-center w-32">Disetujui</th>
              <th class="border p-2 text-center w-32">Ditolak</th>
              <th class="border p-2 text-center w-32">Dibatalkan</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="unit in reportData.unit_report" :key="unit.id" class="hover:bg-gray-50">
              <td class="border p-2 font-medium">{{ unit.name }}</td>
              <td class="border p-2 text-center">{{ unit.approved_count }}</td>
              <td class="border p-2 text-center">{{ unit.rejected_count }}</td>
              <td class="border p-2 text-center">{{ unit.cancelled_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div>
        <h3 class="text-xl font-semibold mb-2">Rekap Penggunaan per Barang</h3>
        <table class="w-full border border-gray-300 rounded-md text-sm">
          <thead>
            <tr class="bg-gray-200">
              <th class="border p-2 text-left">Informasi Barang</th>
              <th class="border p-2 text-center w-40">Jumlah Pemakaian</th>
              <th class="border p-2 text-center w-48">Total Jam Pemakaian</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in reportData.item_report" :key="item.id" class="hover:bg-gray-50">
              <td class="border p-2">
                <div class="font-medium">{{ item.brand }} ({{ item.code }})</div>
                <div v-if="item.barcode" class="text-xs text-gray-500 mt-1">
                  Barcode: {{ item.barcode }}
                </div>
              </td>
              <td class="border p-2 text-center">{{ item.loans_count }} kali</td>
              <td class="border p-2 text-center font-mono">{{ formatHours(item.total_usage_hours) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <div v-else class="text-center text-gray-500 py-10">
        <p>Silakan pilih tipe barang dan bulan untuk menampilkan rekap.</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UsageReport',
  data() {
    return {
      loading: false,
      itemTypeOptions: [],
      activeTypeTab: null,
      filterMonth: '',
      reportData: null,
    };
  },
  computed: {
    // Computed property untuk menampilkan nama bulan yang diformat
    formattedMonth() {
        if (!this.filterMonth) return '';
        const [year, month] = this.filterMonth.split('-');
        const date = new Date(year, month - 1);
        return date.toLocaleString('id-ID', { month: 'long', year: 'numeric' });
    }
  },
  watch: {
    // [DIUBAH] Watcher akan memanggil report secara otomatis
    activeTypeTab() {
      this.generateReport();
    },
    filterMonth() {
      this.generateReport();
    },
  },
  methods: {
    async fetchItemTypes() {
      try {
        const response = await this.$axios.get('/api/items/types');
        this.itemTypeOptions = response.data;
        if (this.itemTypeOptions.length > 0 && !this.activeTypeTab) {
          this.activeTypeTab = this.itemTypeOptions[0].value;
        }
      } catch (error) { console.error("Gagal mengambil tipe barang:", error); }
    },
    async generateReport() {
      if (!this.filterMonth || !this.activeTypeTab) {
        return; // Jangan jalankan jika filter belum siap
      }
      this.loading = true;
      this.reportData = null;
      try {
        const params = {
          month: this.filterMonth,
          item_type: this.activeTypeTab,
        };
        const response = await this.$axios.get('/api/reports', { params });
        this.reportData = response.data;
      } catch (error) {
        this.$swal.fire('Gagal!', 'Tidak dapat menghasilkan rekapitulasi.', 'error');
      } finally {
        this.loading = false;
      }
    },
    // [DITAMBAHKAN] Method untuk memformat jam
    formatHours(hours) {
        if (hours === null || isNaN(hours)) {
            return '0 jam';
        }
        return `${parseFloat(hours).toFixed(1)} jam`;
    }
  },
  async created() {
    // Set bulan default ke bulan ini
    const now = new Date();
    this.filterMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    
    // Ambil tipe item, watcher akan otomatis memicu generateReport pertama kali
    await this.fetchItemTypes();
  }
}
</script>