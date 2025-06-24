<template>
  <div class="p-6 bg-white shadow-md rounded-md">

    <div class="flex items-center justify-between border-b mb-6">
      <div class="flex">
        <button v-for="tab in itemTypeOptions" :key="tab.value" @click="activeTypeTab = tab.value"
                :class="activeTypeTab === tab.value ? 'border-b-4 border-blue-500 text-blue-600' : 'text-gray-600'"
                class="px-6 py-3 hover:text-blue-500 transition capitalize">
          {{ tab.label }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center py-20">
        <p class="text-lg animate-pulse">Menganalisis data untuk <span class="font-bold">{{ activeTypeTab }}</span>...</p>
    </div>
    
    <div v-else-if="predictionData" class="grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in">
        <div class="p-6 border rounded-lg shadow-sm text-center">
            <h3 class="font-bold text-lg text-gray-800">Prediksi Harian</h3>
            <p class="text-sm text-gray-500 mb-4">Total untuk 7 hari ke depan</p>
            <p v-if="predictionData.daily.error" class="text-center text-red-500 my-8">{{ predictionData.daily.error }}</p>
            <p v-else class="text-blue-600 font-extrabold text-7xl">{{ predictionData.daily.prediction_total }}</p>
            <p class="text-gray-600">Peminjaman</p>
        </div>

        <div class="p-6 border rounded-lg shadow-sm text-center">
            <h3 class="font-bold text-lg text-gray-800">Prediksi Mingguan</h3>
            <p class="text-sm text-gray-500 mb-4">Total untuk 4 minggu ke depan</p>
            <p v-if="predictionData.weekly.error" class="text-center text-red-500 my-8">{{ predictionData.weekly.error }}</p>
            <p v-else class="text-teal-600 font-extrabold text-7xl">{{ predictionData.weekly.prediction_total }}</p>
            <p class="text-gray-600">Peminjaman</p>
        </div>

        <div class="p-6 border rounded-lg shadow-sm text-center">
            <h3 class="font-bold text-lg text-gray-800">Prediksi Bulanan</h3>
            <p class="text-sm text-gray-500 mb-4">Total untuk 3 bulan ke depan</p>
            <p v-if="predictionData.monthly.error" class="text-center text-red-500 my-8">{{ predictionData.monthly.error }}</p>
            <p v-else class="text-indigo-600 font-extrabold text-7xl">{{ predictionData.monthly.prediction_total }}</p>
            <p class="text-gray-600">Peminjaman</p>
        </div>
    </div>

    <div v-else class="text-center text-gray-500 py-10">
        <p>Pilih tipe barang untuk menampilkan prediksi.</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PredictionPageFinal',
  data() {
    return {
      loading: true, // Set true di awal agar ada loading saat pertama kali
      itemTypeOptions: [],
      activeTypeTab: null,
      predictionData: null, // Akan berisi { daily, weekly, monthly }
    };
  },
  watch: {
    // Setiap kali tab diganti, panggil API
    activeTypeTab(newType) {
      if (newType) {
        this.fetchPredictionSummary();
      }
    }
  },
  methods: {
    async fetchItemTypes() {
      try {
        const response = await this.$axios.get('/api/items/types');
        this.itemTypeOptions = response.data;
        // Set tab aktif pertama kali, yang akan otomatis memicu watcher
        if (this.itemTypeOptions.length > 0) {
          this.activeTypeTab = this.itemTypeOptions[0].value;
        } else {
            this.loading = false; // Hentikan loading jika tidak ada tipe barang sama sekali
        }
      } catch (error) { 
        console.error("Gagal mengambil tipe barang:", error); 
        this.$swal.fire('Error', 'Gagal memuat tipe barang.', 'error');
        this.loading = false;
      }
    },
    async fetchPredictionSummary() {
      if (!this.activeTypeTab) return;
      
      this.loading = true;
      this.predictionData = null;
      try {
        const response = await this.$axios.get(`/api/predictions/generate/${this.activeTypeTab}`);
        this.predictionData = response.data;
      } catch (error) {
        const message = error.response?.data?.message || 'Tidak dapat menghasilkan prediksi.';
        this.$swal.fire('Gagal!', message, 'error');
      } finally {
        this.loading = false;
      }
    },
  },
  created() {
    this.fetchItemTypes();
  }
};
</script>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>