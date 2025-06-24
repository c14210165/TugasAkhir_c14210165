<template>
  <div class="p-6 bg-white shadow-md rounded-md">

    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="filterByType('Semua')"
                    :class="activeTypeTab === 'Semua' ? 'border-b-4 border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                Semua
            </button>
            <button v-for="tab in itemTypeOptions" :key="tab.value" @click="filterByType(tab.value)"
                    :class="activeTypeTab === tab.value ? 'border-b-4 border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 capitalize">
            {{ tab.label }}
            </button>
        </nav>
    </div>

    <div class="mt-4">
        <div v-if="loading" class="text-center py-20 text-gray-500">Memuat jadwal...</div>
        <FullCalendar v-else :options="calendarOptions" />
    </div>
  </div>

  <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white w-full max-w-3xl p-6 rounded-lg shadow-lg overflow-y-auto max-h-[90vh]">
      <div class="flex justify-between items-center mb-4 border-b pb-3">
          <h2 class="text-2xl font-bold text-gray-800">Detail Peminjaman</h2>
          <button @click="closeDetailModal" class="text-gray-500 hover:text-red-600 font-bold text-3xl leading-none">&times;</button>
      </div>

      <div v-if="selectedLoanForDetail" class="space-y-6 text-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold text-lg mb-2 text-gray-700 border-l-4 border-blue-500 pl-3">Informasi Utama</h3>
                <div class="space-y-2 pl-4">
                    <p><strong>Status:</strong> <span class="px-2 py-1 rounded-full text-xs font-semibold capitalize" :class="getStatusClass(selectedLoanForDetail)">{{ getStatusText(selectedLoanForDetail) }}</span></p>
                    <p><strong>Pemohon:</strong> {{ selectedLoanForDetail.requester?.name || 'N/A' }}</p>
                    <p v-if="selectedLoanForDetail.requester?.unit"><strong>Unit/Jurusan:</strong> {{ selectedLoanForDetail.requester.unit.name }}</p>
                    <p><strong>Barang:</strong> {{ selectedLoanForDetail.item ? `${selectedLoanForDetail.item.brand} (${selectedLoanForDetail.item.code})` : 'N/A' }}</p>
                </div>
            </div>
            <div>
                <h3 class="font-semibold text-lg mb-2 text-gray-700 border-l-4 border-blue-500 pl-3">Jadwal & Tujuan</h3>
                <div class="space-y-2 pl-4">
                    <p><strong>Jadwal Pinjam:</strong> {{ formatDate(selectedLoanForDetail.start_at) }}</p>
                    <p><strong>Tenggat Kembali:</strong> {{ formatDate(selectedLoanForDetail.end_at) }}</p>
                    <p><strong>Lokasi:</strong> {{ selectedLoanForDetail.location || 'N/A' }}</p>
                    <p><strong>Tujuan:</strong> <span class="whitespace-pre-wrap">{{ selectedLoanForDetail.purpose || 'N/A' }}</span></p>
                </div>
            </div>
        </div>
        <hr/>
        <div>
            <h3 class="font-semibold text-lg mb-2 text-gray-700 border-l-4 border-blue-500 pl-3">Riwayat Proses</h3>
            <div class="space-y-2 pl-4">
                <p><strong>Dibuat Oleh:</strong> {{ selectedLoanForDetail.createdBy?.name || 'N/A' }} <span class="text-gray-500">pada {{ formatDate(selectedLoanForDetail.created_at) }}</span></p>
                <p><strong>Disetujui Unit:</strong> {{ selectedLoanForDetail.unit_approver?.name || 'Belum diproses' }}</p>
                <p><strong>Disetujui PTIK:</strong> {{ selectedLoanForDetail.ptik_approver?.name || 'Belum diproses' }} <span v-if="selectedLoanForDetail.approved_at" class="text-gray-500">pada {{ formatDate(selectedLoanForDetail.approved_at) }}</span></p>
                <p><strong>Diserahkan Oleh:</strong> {{ selectedLoanForDetail.checkedOutBy?.name || 'Belum diserahkan' }} <span v-if="selectedLoanForDetail.borrowed_at" class="text-gray-500">pada {{ formatDate(selectedLoanForDetail.borrowed_at) }}</span></p>
                <p><strong>Diterima Kembali Oleh:</strong> {{ selectedLoanForDetail.checkedInBy?.name || 'Belum dikembalikan' }} <span v-if="selectedLoanForDetail.returned_at" class="text-gray-500">pada {{ formatDate(selectedLoanForDetail.returned_at) }}</span></p>
            </div>
        </div>
        <div class="mt-8 pt-4 border-t flex justify-end">
            <button @click="closeDetailModal" type="button" class="px-5 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">Tutup</button>
        </div>
      </div>
    </div>
    </div>
</template>

<script>
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

export default {
  name: 'schedulesPage',
  components: {
    FullCalendar,
  },
  data() {
    return {
      // Opsi untuk FullCalendar
      calendarOptions: {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth', // Tampilan awal bulanan
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,dayGridWeek' // Pilihan view bulan & minggu
        },
        events: [], // Data event akan diisi dari API
        eventClick: this.handleEventClick, // Fungsi saat event diklik
        locale: 'id', // Bahasa Indonesia
        buttonText: {
            today: 'Hari Ini',
            month: 'Bulan',
            week: 'Minggu',
        }
      },
      loading: true, // State untuk loading
      itemTypeOptions: [], // Untuk menampung tipe barang dari API
      activeTypeTab: 'Semua',
      // State untuk modal detail
      showDetailModal: false,
      selectedLoanForDetail: null,
    };
  },
  watch: {
    // Watcher ini akan secara otomatis memanggil API setiap kali tab diganti
    activeTypeTab() {
        this.fetchCalendarEvents();
    }
  },
  methods: {
    // Fungsi untuk mengambil data event dari API
    async fetchCalendarEvents() {
        this.loading = true;
        try {
            let params = {};
            if (this.activeTypeTab !== 'Semua') {
                params.type = this.activeTypeTab;
            }
            // Pastikan nama endpoint ini sesuai dengan yang ada di routes/api.php
            const response = await this.$axios.get('/api/schedules', { params });
            this.calendarOptions.events = response.data;
        } catch (error) {
            this.$swal.fire('Gagal!', 'Tidak dapat memuat data jadwal dari server.', 'error');
        } finally {
            this.loading = false;
        }
    },
    async fetchItemTypeOptions() {
        try {
            const response = await this.$axios.get('/api/items/types');
            this.itemTypeOptions = response.data;
        } catch (error) {
            console.error("Gagal mengambil tipe barang:", error);
        }
    },
    filterByType(type) {
        this.activeTypeTab = type;
    },
    // Fungsi yang dijalankan saat sebuah event di kalender diklik
    async handleEventClick(clickInfo) {
      const loanId = clickInfo.event.extendedProps.loan_id;
      try {
        // Pastikan endpoint ini ada di routes/api.php
        const response = await this.$axios.get(`/api/loans/${loanId}/detail`); 
        this.selectedLoanForDetail = response.data;
        this.showDetailModal = true;
      } catch (error) {
        this.$swal.fire('Gagal!', 'Tidak dapat memuat detail peminjaman.', 'error');
      }
    },
    // Fungsi untuk menutup modal
    closeDetailModal() {
        this.showDetailModal = false;
        this.selectedLoanForDetail = null;
    },
    // Fungsi format tanggal (bisa dicopy dari komponen lain)
    formatDate(dateString) {
      if (!dateString) return null;
      const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    getStatusText(loan) {
        if (!loan || !loan.status) return '';
        if (loan.is_late) { return 'Terlambat'; }
        return loan.status.replace(/_/g, ' ');
    },
    getStatusClass(loan) {
        if (!loan || !loan.status) return 'bg-gray-200 text-gray-800';
        if (loan.is_late) { return 'bg-red-200 text-red-900 font-bold'; }
        
        const classes = {
            APPROVED: 'bg-blue-100 text-blue-800',
            ACTIVE: 'bg-green-100 text-green-800',
            COMPLETED: 'bg-indigo-100 text-indigo-800',
            REJECTED: 'bg-red-100 text-red-800', 
            DECLINED: 'bg-red-100 text-red-800', 
            CANCELLED: 'bg-gray-100 text-gray-800',
        };
        return classes[loan.status] || 'bg-gray-200 text-gray-800';
    },
  },
  created() {
    this.fetchItemTypeOptions();
  },
  activated() {
    console.log('Halaman Jadwal diaktifkan, mengambil data terbaru...');
    this.fetchCalendarEvents();
  },
  
  // [TAMBAHKAN INI JUGA] Sebagai fallback jika Anda tidak menggunakan <keep-alive>
  // Method ini berjalan sekali saat komponen "dipasang" ke DOM.
  mounted() {
    this.fetchCalendarEvents();
  }
};
</script>
