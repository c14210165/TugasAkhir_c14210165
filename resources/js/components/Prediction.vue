<template>
  <div class="p-6 bg-white shadow-md rounded-md">
    <!-- Tombol Tabulasi Tipe Barang -->
    <div class="flex items-center justify-between border-b mb-6">
      <div class="flex overflow-x-auto">
        <button
          v-for="tab in itemTypeOptions"
          :key="tab.value"
          @click="changeTab(tab.value)"
          :class="activeTypeTab === tab.value ? 'border-b-4 border-blue-500 text-blue-600' : 'text-gray-600'"
          class="px-6 py-3 hover:text-blue-500 transition capitalize flex-shrink-0"
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Tampilan Loading -->
    <div v-if="loading" class="text-center py-20">
      <p class="text-lg animate-pulse">
        Menganalisis data untuk <span class="font-bold">{{ activeTypeTab }}</span>...
      </p>
    </div>
    
    <!-- Tampilan Hasil Prediksi -->
    <div v-else-if="predictionData" class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-fade-in">
      <!-- Kartu Prediksi Harian -->
      <div class="p-6 border rounded-lg shadow-sm flex flex-col">
        <div class="text-center">
            <h3 class="font-bold text-lg text-gray-800">Prediksi Harian</h3>
            <p class="text-sm text-gray-500 mb-4">Total untuk 7 hari ke depan</p>
            <!-- Tampilkan error jika ada, jika tidak tampilkan angka -->
            <p v-if="predictionData.daily.error" class="text-center text-red-500 my-8 px-4 text-sm">{{ predictionData.daily.error }}</p>
            <template v-else>
                <p class="text-blue-600 font-extrabold text-7xl">{{ predictionData.daily.prediction_total }}</p>
                <p class="text-gray-600">Peminjaman</p>
            </template>
        </div>
        <div class="mt-6 flex-grow" style="position: relative; height:250px" v-if="!predictionData.daily.error">
            <canvas :ref="el => setChartCanvasRef('daily', el)"></canvas>
        </div>
      </div>

      <!-- Kartu Prediksi Mingguan -->
      <div class="p-6 border rounded-lg shadow-sm flex flex-col">
        <div class="text-center">
            <h3 class="font-bold text-lg text-gray-800">Prediksi Mingguan</h3>
            <p class="text-sm text-gray-500 mb-4">Total untuk 4 minggu ke depan</p>
            <p v-if="predictionData.weekly.error" class="text-center text-red-500 my-8 px-4 text-sm">{{ predictionData.weekly.error }}</p>
            <template v-else>
                <p class="text-teal-600 font-extrabold text-7xl">{{ predictionData.weekly.prediction_total }}</p>
                <p class="text-gray-600">Peminjaman</p>
            </template>
        </div>
        <div class="mt-6 flex-grow" style="position: relative; height:250px" v-if="!predictionData.weekly.error">
            <canvas :ref="el => setChartCanvasRef('weekly', el)"></canvas>
        </div>
      </div>

      <!-- Kartu Prediksi Bulanan -->
      <div class="p-6 border rounded-lg shadow-sm flex flex-col">
        <div class="text-center">
            <h3 class="font-bold text-lg text-gray-800">Prediksi Bulanan</h3>
            <p class="text-sm text-gray-500 mb-4">Total untuk 3 bulan ke depan</p>
            <p v-if="predictionData.monthly.error" class="text-center text-red-500 my-8 px-4 text-sm">{{ predictionData.monthly.error }}</p>
            <template v-else>
                <p class="text-indigo-600 font-extrabold text-7xl">{{ predictionData.monthly.prediction_total }}</p>
                <p class="text-gray-600">Peminjaman</p>
            </template>
        </div>
        <div class="mt-6 flex-grow" style="position: relative; height:250px" v-if="!predictionData.monthly.error">
            <canvas :ref="el => setChartCanvasRef('monthly', el)"></canvas>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
  name: 'PredictionPageFinal',
  data() {
    return {
      loading: true,
      itemTypeOptions: [],
      activeTypeTab: null,
      predictionData: null,
      charts: { daily: null, weekly: null, monthly: null },
      chartCanvasRefs: { daily: null, weekly: null, monthly: null },
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
    // Fungsi ini kembali memanggil API seperti seharusnya
    async fetchItemTypes() {
      try {
        // Asumsi endpoint ini mengembalikan array seperti [{ label: 'Hardware', value: 'Hardware' }]
        const response = await this.$axios.get('/api/items/types'); 
        this.itemTypeOptions = response.data;
        if (this.itemTypeOptions.length > 0) {
          this.activeTypeTab = this.itemTypeOptions[0].value;
        } else {
          this.loading = false;
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
      this.destroyAllCharts();
      try {
        // Efek loading palsu agar tidak terlalu cepat
        await new Promise(resolve => setTimeout(resolve, 500));
        const response = await this.$axios.get(`/api/predictions/generate/${this.activeTypeTab}`);
        this.predictionData = response.data;
      } catch (error) {
        const message = error.response?.data?.message || 'Tidak dapat menghasilkan prediksi.';
        this.$swal.fire('Gagal!', message, 'error');
      } finally {
        this.loading = false;
        this.$nextTick(() => {
            this.renderAllCharts();
        });
      }
    },
    setChartCanvasRef(type, el) {
        if (el) { this.chartCanvasRefs[type] = el; }
    },
    renderAllCharts() {
      if (!this.predictionData) return;
      this.renderChart('daily', 'rgba(59, 130, 246, 0.2)', 'rgba(59, 130, 246, 1)');
      this.renderChart('weekly', 'rgba(13, 148, 136, 0.2)', 'rgba(13, 148, 136, 1)');
      this.renderChart('monthly', 'rgba(79, 70, 229, 0.2)', 'rgba(79, 70, 229, 1)');
    },
    renderChart(type, colorFill, colorBorder) {
        const canvas = this.chartCanvasRefs[type];
        if (!canvas) return;
        
        const data = this.predictionData[type];
        // PENTING: Jangan render chart jika backend mengembalikan pesan error untuk interval ini
        if (!data || data.error) return; 

        const chartData = {
            labels: [...data.historical_chart.labels, ...data.prediction_chart.labels],
            datasets: [
                {
                    label: 'Data Historis', data: data.historical_chart.data,
                    borderColor: 'rgba(107, 114, 128, 1)', borderDash: [5, 5], tension: 0.2, fill: false, pointRadius: 1,
                },
                {
                    label: 'Prediksi',
                    data: [...Array(data.historical_chart.data.length - 1).fill(null), data.historical_chart.data.slice(-1)[0], ...data.prediction_chart.data],
                    borderColor: colorBorder, backgroundColor: colorFill, tension: 0.2, fill: 'origin', pointRadius: 1,
                },
            ],
        };
        this.charts[type] = new Chart(canvas.getContext('2d'), {
            type: 'line', data: chartData,
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'top' }, tooltip: { mode: 'index', intersect: false } },
                scales: { 
                    y: { beginAtZero: true },
                    x: { ticks: { autoSkip: true, maxTicksLimit: 12 } } // Tampilkan lebih banyak label tanggal
                },
            },
        });
    },
    destroyAllCharts() {
      Object.values(this.charts).forEach(chart => { if (chart) chart.destroy(); });
    },
    changeTab(tabValue) {
        this.activeTypeTab = tabValue;
    }
  },
  created() {
    this.fetchItemTypes();
  },
  beforeUnmount() {
      this.destroyAllCharts();
  }
};
</script>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-in-out; }
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
