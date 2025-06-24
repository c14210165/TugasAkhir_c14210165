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
              <!-- <th class="border px-4 py-3">Unit/Jurusan</th> -->
              <th class="border px-4 py-3">Pemohon</th>
              <th class="border px-4 py-3">Keperluan</th>
              <th class="border px-4 py-3">Jadwal Pinjam</th>
              <th class="border px-4 py-3">Jadwal Kembali</th>
              <!-- <th class="border px-4 py-3">Entry User</th>
              <th class="border px-4 py-3">Respon Admin</th> -->
              <th class="border px-4 py-3">Tanggal Ambil</th>
              <th class="border px-4 py-3">Tanggal Kembali</th>
              <th class="border px-4 py-3">Keterangan</th>
              <!-- <th class="border px-4 py-3">Tanggal Kembali</th>
              <th class="border px-4 py-3">Operator Kembali</th>
              <th class="border px-4 py-3">Status</th> -->
              <th class="border px-4 py-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(loan, index) in loans" :key="loan.id" class="hover:bg-gray-50 transition">
              <td class="border px-4 py-2">{{ pagination.from + index }}</td>
              <td class="border px-4 py-3 font-mono text-xs">{{ loan.item?.barcode || 'N/A' }}</td>
              <td class="border px-4 py-2 font-mono text-xs">{{ loan.item ? `${loan.item.brand} (${loan.item.code})` : 'N/A' }}</td>
              <td class="border px-4 py-3 capitalize">{{ loan.item_type }}</td>
              <!-- <td class="border px-4 py-2">{{ loan.created_by.name }}</td> -->
              <td class="border px-4 py-3">{{ loan.requester.name }}</td>
              <td class="border px-4 py-3 text-sm text-gray-600">{{ loan.purpose }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.start_at) || '-' }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.end_at) }}</td>
              <!-- <td class="border px-4 py-2">{{ formatDate(loan.created_at) }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.responded_at) }}</td> -->
              <td class="border px-4 py-2">{{ formatDate(loan.borrowed_at) || '-' }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.returned_at) || '-'}}</td>
              <td class="border border-black px-4 py-2 text-center text-sm" :class="{'text-red-600 font-semibold': loan.is_late}">
                  {{ loan.lateness_info }}
              </td>
              <!-- <td class="border px-4 py-3">{{ loan.checked_out_by?.name || '-' }}</td> -->
              <td class="border px-4 py-2 text-center">
                <button @click="openDetailModal(loan)" title="Lihat Detail" class="text-gray-500 hover:text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      <p v-if="!loading && loans.length === 0" class="text-gray-500 text-center mt-4">Tidak ada data yang cocok dengan filter.</p>
    </div>

    <div v-if="!loading && pagination.total > 0 && loans.length > 0" class="flex justify-between items-center mt-4 text-sm text-gray-600">
        <div>Menampilkan <span class="font-semibold">{{ pagination.from }}</span> sampai <span class="font-semibold">{{ pagination.to }}</span> dari <span class="font-semibold">{{ pagination.total }}</span> data</div>
            <div class="flex items-center gap-2">
                <button :disabled="!pagination.prev_page_url" @click="changePage(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
                <span>Page {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
                <button :disabled="!pagination.next_page_url" @click="changePage(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
            </div>
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
                <div v-if="selectedLoanForDetail.is_late" class="mt-2 p-2 bg-red-50 border-l-4 border-red-400 text-red-700">
                    <p><strong>Keterangan:</strong> {{ selectedLoanForDetail.lateness_info }}</p>
                </div>
                <div v-if="selectedLoanForDetail.rejection_reason" class="mt-2 p-2 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800">
                    <p><strong>Alasan Ditolak/Dibatalkan:</strong> {{ selectedLoanForDetail.rejection_reason }}</p>
                </div>
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
import _ from 'lodash';

export default {
  // Ganti nama komponen sesuai nama file Anda
  name: 'LoanManagement',
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
        { name: 'Aktif', status: 'ACTIVE' },
        { name: 'Dikembalikan', status: 'COMPLETED' },
      ],
      showEditModal: false,
      isSubmitting: false,
      editForm: {},
      editingLoanId: null,
      validationErrors: {},
      availableItemsForEdit: [],
      originalItemId: null,
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
        const response = await this.$axios.get('/api/loans', { params: apiParams });
        this.loans = response.data.data;
        this.pagination = response.data;
      } catch (error) {
        this.$swal.fire('Gagal!', 'Tidak dapat mengambil data peminjaman.', 'error');
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
    filterByStatus(status) {
      this.filters.status = status;
    },
    changePage(page) {
      if (page > 0 && page <= this.pagination.last_page) {
        this.filters.page = page;
        this.fetchLoans();
      }
    },
    async checkOut(loan) {
        const result = await this.$swal.fire({
            title: 'Serahkan Barang?',
            text: `Anda akan mencatat bahwa barang telah diserahkan kepada ${loan.requester.name}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Serahkan!',
            cancelButtonText: 'Batal'
        });
        if (result.isConfirmed) {
            this.processingId = loan.id;
            try {
                await this.$axios.post(`/api/loans/${loan.id}/checkout`);
                this.$swal.fire('Berhasil!', 'Barang telah diserahkan dan status peminjaman menjadi AKTIF.', 'success');
                this.fetchLoans();
            } catch (error) {
                const message = error.response?.data?.message || 'Terjadi kesalahan.';
                this.$swal.fire('Gagal!', message, 'error');
            } finally {
                this.processingId = null;
            }
        }
    },
    async cancelLoan(loan) {
      const result = await this.$swal.fire({
          title: 'Batalkan Peminjaman?',
          text: `Anda yakin ingin membatalkan peminjaman untuk ${loan.requester.name}? Status akan diubah menjadi CANCELLED.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Batalkan!',
          cancelButtonText: 'Tidak',
          confirmButtonColor: '#ef4444',
      });
      if (result.isConfirmed) {
          this.processingId = loan.id;
          try {
              await this.$axios.post(`/api/loans/${loan.id}/cancel`);
              this.$swal.fire('Dibatalkan!', 'Peminjaman telah dibatalkan.', 'success');
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
      this.selectedLoanForDetail = loan;
      this.showDetailModal = true;
    },
    openDetailModal(loan) {
        this.selectedLoanForDetail = loan;
        this.showDetailModal = true;
    },
    closeDetailModal() {
        this.showDetailModal = false;
        // Tidak perlu reset data karena akan diisi ulang saat dibuka lagi
    },
    async openEditModal(loan) {
      this.validationErrors = {};
      this.editingLoanId = loan.id;
      this.originalItemId = loan.item_id; // Simpan ID barang asli
      
      this.editForm = { 
        ...loan,
        start_at: this.toDatetimeLocal(loan.start_at),
        end_at: this.toDatetimeLocal(loan.end_at),
      };
      
      this.showEditModal = true;
      // Ambil daftar barang yang bisa dipilih untuk ditukar
      await this.fetchAvailableItemsForEdit(loan.item_type, loan.item);
    },
    async fetchAvailableItemsForEdit(itemType, currentItem) {
        this.availableItemsForEdit = []; // Kosongkan dulu
        try {
            const response = await this.$axios.get('/api/items/available', {
                params: { type: itemType }
            });
            
            // Tambahkan item yang sedang dipakai sekarang ke daftar pilihan,
            // letakkan di paling atas.
            let items = response.data;
            if (currentItem) {
                // Pastikan tidak ada duplikat jika item saat ini kebetulan available
                items = items.filter(item => item.id !== currentItem.id);
                items.unshift(currentItem);
            }
            this.availableItemsForEdit = items;

        } catch (error) {
            console.error("Gagal mengambil daftar item untuk diedit:", error);
            this.$swal.fire('Gagal!', 'Tidak dapat memuat daftar barang pengganti.', 'error');
        }
    },
    closeEditModal() {
      this.showEditModal = false;
      this.editForm = {};
      this.editingLoanId = null;
    },
    async submitUpdateLoan() {
      this.isSubmitting = true;
      this.validationErrors = {};
      try {
        await this.$axios.put(`/api/loans/${this.editingLoanId}`, this.editForm);
        this.closeEditModal();
        await this.fetchLoans();
        this.$swal.fire('Berhasil!', 'Data peminjaman telah diupdate.', 'success');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.validationErrors = error.response.data.errors;
        } else {
          const message = error.response?.data?.message || 'Terjadi kesalahan saat mengupdate.';
          this.$swal.fire('Gagal!', message, 'error');
        }
      } finally {
        this.isSubmitting = false;
      }
    },

    // --- Utility Methods ---
    formatDate(dateString) {
      if (!dateString) return null;
      const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    toDatetimeLocal(isoString) {
        if (!isoString) return '';
        const date = new Date(isoString);
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
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
            ACTIVE: 'bg-green-100 text-green-800',
            COMPLETED: 'bg-indigo-100 text-indigo-800',
        };
        return classes[loan.status] || 'bg-gray-200 text-gray-900';
    },
  },
  created() {
    this.fetchLoans();
    this.fetchItemTypeOptions();
  },
};
</script>