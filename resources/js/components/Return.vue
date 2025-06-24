<template>
  <div class="p-6 bg-white shadow-md rounded-md">

    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button v-for="tab in tabs" :key="tab.status" @click="filterByStatus(tab.status)"
                :class="filters.status === tab.status ? 'border-b-4 border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 capitalize">
          {{ tab.name }}
        </button>
      </nav>
    </div>
    
    <div class="flex justify-between items-center my-4 flex-wrap gap-4">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-700">Filter Tipe:</span>
                <div class="flex items-center bg-gray-100 p-1 rounded-lg">
                     <button @click="filterByType('Semua')" :class="filters.type === 'Semua' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:bg-gray-200'" class="px-3 py-1 text-sm rounded-md transition-all">Semua</button>
                     <button v-for="type in itemTypeOptions" :key="type.value" @click="filterByType(type.value)" :class="filters.type === type.value ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:bg-gray-200'" class="px-3 py-1 text-sm rounded-md transition-all capitalize">{{ type.label }}</button>
                </div>
            </div>
             <div class="flex items-center gap-2">
                <label class="mr-2 text-sm">Tampilkan</label>
                <select v-model="filters.perPage" class="border border-gray-300 rounded px-2 py-1 text-sm">
                  <option v-for="size in [5, 10, 25, 50]" :key="size" :value="size">{{ size }}</option>
                </select>
            </div>
        </div>
        <div>
            <input type="text" v-model="filters.search" placeholder="Cari pemohon atau kode barang..." class="border border-gray-300 rounded-md px-3 py-2 w-64" />
        </div>
    </div>

    <div v-if="loading" class="text-center py-16"><p class="text-gray-500">Memuat data peminjaman...</p></div>

    <div v-if="!loading" class="overflow-x-auto">
        <table v-if="loans.length > 0" class="w-full border border-gray-300 rounded-md">
          <thead>
            <tr class="bg-gray-200 text-gray-700 text-left text-sm">
              <th class="border px-4 py-3">#</th>
              <th class="border px-4 py-3">Barcode</th>
              <th class="border px-4 py-3">Barang</th>
              <th class="border px-4 py-3">Tipe Barang</th>
              <th class="border px-4 py-3">Unit/Jurusan</th>
              <th class="border px-4 py-3">Pemohon</th>
              <th class="border px-4 py-3">Jadwal Pinjam</th>
              <th class="border px-4 py-3">Jadwal Kembali</th>
              <th class="border px-4 py-3">Tanggal Kembali</th>
              <th class="border px-4 py-3">Operator Kembali</th>
              <th class="border px-4 py-3">Keterangan</th>
              <!-- <th class="border px-4 py-3">Status</th> -->
              <th class="border px-4 py-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(loan, index) in loans" :key="loan.id" class="hover:bg-gray-50 transition">
              <td class="border px-4 py-2">{{ pagination.from + index }}</td>
              <td class="border px-4 py-3 font-mono text-xs">{{ loan.item?.barcode || 'N/A' }}</td>
              <td class="border px-4 py-2 font-mono text-xs">{{ loan.item ? `${loan.item.brand} (${loan.item.code})` : 'N/A' }}</td>
              <td class="border px-4 py-3 capitalize">{{ loan.item_type }}</td>
              <td class="border px-4 py-2">{{ loan.created_by.name }}</td>
              <td class="border px-4 py-3">{{ loan.requester.name }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.start_at) || '-' }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.end_at) }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.returned_at) || '-'}}</td>
              <td class="border px-4 py-3">{{ loan.checked_in_by?.name || '-'}}</td>
              <td class="border border-black px-4 py-2 text-center text-sm" :class="{'text-red-600 font-semibold': loan.is_late}">
                  {{ loan.lateness_info }}
              </td>
              <!-- <td class="border px-4 py-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold capitalize" :class="getStatusClass(loan)">
                  {{ getStatusText(loan) }}
                </span>
              </td> -->
              <td class="border px-4 py-2">
                <div class="flex items-center justify-center gap-4">
                  <template v-if="filters.status === 'ACTIVE'">
                    <button @click="checkIn(loan)" title="Barang Dikembalikan"
                            class="px-4 py-2 bg-green-500 text-white text-xs rounded-md hover:bg-green-600 disabled:opacity-50 font-semibold"
                            :disabled="processingId === loan.id">
                            Tandai Kembali
                    </button>
                  </template>
                  <button @click="openDetailModal(loan)" title="Lihat Detail" class="text-gray-500 hover:text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      <p v-if="!loading && loans.length === 0" class="text-gray-500 text-center mt-4">
        Tidak ada data peminjaman yang cocok dengan filter.
      </p>
    </div>

    <div v-if="!loading && pagination.total > 0" class="flex justify-between items-center mt-4 text-sm text-gray-600">
        <div>Menampilkan <span class="font-semibold">{{ pagination.from }}</span> sampai <span class="font-semibold">{{ pagination.to }}</span> dari <span class="font-semibold">{{ pagination.total }}</span> data</div>
        <div class="flex items-center gap-2">
            <button :disabled="!pagination.prev_page_url" @click="changePage(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
            <span>Page {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="changePage(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
        </div>
    </div>
  </div>

  <div v-if="showDetailModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-3xl p-6 rounded-lg shadow-lg overflow-y-auto max-h-[90vh]">
      <div class="flex justify-between items-center mb-4 border-b pb-3">
          <h2 class="text-2xl font-bold">Detail Pengembalian</h2>
          <button @click="closeDetailModal" class="text-gray-500 hover:text-red-600 font-bold text-2xl">&times;</button>
      </div>

      <div v-if="selectedLoanForDetail" class="space-y-6 text-sm">
        
        <div class="p-4 bg-gray-50 rounded-lg border">
            <h3 class="font-semibold text-base mb-2 text-gray-800">Ringkasan Peminjaman</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                <p><strong>Pemohon:</strong> {{ selectedLoanForDetail.requester?.name || 'N/A' }}</p>
                <p><strong>Barang:</strong> {{ selectedLoanForDetail.item ? `${selectedLoanForDetail.item.brand} (${selectedLoanForDetail.item.code})` : 'N/A' }}</p>
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-lg mb-2 text-gray-800">Analisis Waktu & Durasi</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 p-3 rounded-md">
                    <p class="font-semibold text-blue-800">Jadwal</p>
                    <hr class="my-1 border-blue-200">
                    <p><strong>Diambil:</strong> {{ formatDate(selectedLoanForDetail.borrowed_at) || 'Belum diambil' }}</p>
                    <p><strong>Tenggat Kembali:</strong> {{ formatDate(selectedLoanForDetail.end_at) }}</p>
                </div>
                <div class="bg-green-50 p-3 rounded-md">
                    <p class="font-semibold text-green-800">Realisasi</p>
                    <hr class="my-1 border-green-200">
                    <p><strong>Dikembalikan:</strong> {{ formatDate(selectedLoanForDetail.returned_at) || 'Belum kembali' }}</p>
                    <p><strong>Status:</strong> <span class="font-bold" :class="selectedLoanForDetail.is_late ? 'text-red-600' : 'text-green-600'">{{ selectedLoanForDetail.is_late ? 'Terlambat' : 'Tepat Waktu' }}</span></p>
                </div>
            </div>
            <div class="mt-3 text-center bg-gray-100 p-2 rounded-md">
                <p><strong>Total Durasi Peminjaman:</strong> <span class="font-bold">{{ calculateDuration(selectedLoanForDetail.borrowed_at, selectedLoanForDetail.returned_at) }}</span></p>
            </div>
        </div>

        <div>
            <h3 class="font-semibold text-lg mb-2 text-gray-800">Akuntabilitas Petugas</h3>
            <div class="space-y-2">
                <p><strong>Diserahkan Oleh:</strong> {{ selectedLoanForDetail.checked_out_by?.name || 'N/A' }}</p>
                <p><strong>Diterima Kembali Oleh:</strong> {{ selectedLoanForDetail.checked_in_by?.name || 'N/A' }}</p>
            </div>
        </div>
        
        <div class="mt-8 flex justify-end">
            <button @click="closeDetailModal" type="button" class="px-5 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import _ from 'lodash';

export default {
  name: 'ReturnPage',
  data() {
    return {
      loans: [],
      loading: true,
      filters: { 
        status: 'ACTIVE',
        type: 'Semua',
        search: '',
        page: 1, 
        perPage: 10 
      },
      pagination: {},
      processingId: null,
      tabs: [
        { name: 'Belum Kembali', status: 'ACTIVE' },
        { name: 'Dikembalikan', status: 'COMPLETED' },
      ],
      showDetailModal: false,
      selectedLoanForDetail: null,
      itemTypeOptions: [],
    };
  },
  watch: {
    'filters.search': _.debounce(function() { this.filters.page = 1; this.fetchLoans(); }, 500),
    'filters.perPage': function() { this.filters.page = 1; this.fetchLoans(); },
    'filters.status': function() { this.filters.page = 1; this.fetchLoans(); },
    'filters.type': function() { this.filters.page = 1; this.fetchLoans(); },
  },
  methods: {
    async fetchLoans() {
      this.loading = true;
      try {
        let apiParams = { ...this.filters };
        if (apiParams.type === 'Semua') {
            delete apiParams.type;
        }
        const response = await this.$axios.get('/api/returns', { params: apiParams });
        this.loans = response.data.data;
        this.pagination = response.data;
      } catch (error) {
        this.$swal.fire('Gagal!', 'Tidak dapat mengambil data peminjaman dari server.', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchItemTypeOptions() {
        try {
            const response = await this.$axios.get('/api/items/types');
            this.itemTypeOptions = response.data;
        } catch (error) {
            console.error("Gagal mengambil pilihan tipe barang:", error);
        }
    },
    filterByType(type) {
        this.filters.type = type;
    },
    // Method untuk mengubah filter saat tab diklik
    filterByStatus(status) {
        this.filters.status = status;
    },
    changePage(page) {
      if (page > 0 && page <= this.pagination.last_page) {
        this.filters.page = page;
      }
    },
    async checkIn(loan) {
        const result = await this.$swal.fire({
            title: 'Tandai Barang Telah Kembali?',
            html: `Konfirmasi bahwa <b>${loan.item.brand} (${loan.item.code})</b><br>telah dikembalikan oleh <b>${loan.requester.name}</b>.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Sudah Kembali!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#16a34a',
        });
        if (result.isConfirmed) {
            this.processingId = loan.id;
            try {
                await this.$axios.post(`/api/loans/${loan.id}/return`);
                this.$swal.fire('Berhasil!', 'Barang telah ditandai kembali.', 'success');
                this.fetchLoans();
            } catch (error) {
                const message = error.response?.data?.message || 'Terjadi kesalahan.';
                this.$swal.fire('Gagal!', message, 'error');
            } finally {
                this.processingId = null;
            }
        }
    },
    showDetails(loan) {
      this.openDetailModal(loan);
    },
    openDetailModal(loan) {
        this.selectedLoanForDetail = loan;
        this.showDetailModal = true;
    },
    closeDetailModal() {
        this.showDetailModal = false;
    },
    formatDate(dateString) {
      if (!dateString) return null;
      const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    getStatusText(loan) {
        if (loan.is_late) { return 'Terlambat'; }
        return loan.status.replace(/_/g, ' ');
    },
    getStatusClass(loan) {
        if (loan.is_late) { return 'bg-red-200 text-red-900 font-bold'; }
        const classes = {
            ACTIVE: 'bg-green-100 text-green-800',
            COMPLETED: 'bg-indigo-100 text-indigo-800',
        };
        return classes[loan.status] || 'bg-gray-200 text-gray-900';
    },
    calculateDuration(start, end) {
        if (!start || !end) {
            return 'Belum Selesai';
        }
        
        const startDate = new Date(start);
        const endDate = new Date(end);
        
        let diff = endDate.getTime() - startDate.getTime();
        
        let days = Math.floor(diff / (1000 * 60 * 60 * 24));
        diff -= days * (1000 * 60 * 60 * 24);
        
        let hours = Math.floor(diff / (1000 * 60 * 60));
        diff -= hours * (1000 * 60 * 60);
        
        let mins = Math.floor(diff / (1000 * 60));

        let result = [];
        if (days > 0) result.push(`${days} hari`);
        if (hours > 0) result.push(`${hours} jam`);
        if (mins > 0) result.push(`${mins} menit`);

        return result.length > 0 ? result.join(', ') : 'Kurang dari semenit';
    },
  },
  created() {
    this.fetchLoans();
    this.fetchItemTypeOptions();
  },
};
</script>