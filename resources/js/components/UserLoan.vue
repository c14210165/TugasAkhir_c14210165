<template>
  <div class="p-6 bg-white shadow-md rounded-md">
    <div class="flex justify-between items-center mb-4 border-b">
      <div class="flex">
        <button v-for="tab in tabs" :key="tab.status" @click="filterByStatus(tab.status)"
                :class="filters.status === tab.status ? 'border-b-4 border-blue-500 text-blue-600' : 'text-gray-600'"
                class="px-6 py-3 hover:text-blue-500 transition capitalize">
          {{ tab.name }}
        </button>
      </div>
    </div>
    
    <div class="flex justify-between items-center mb-4 flex-wrap gap-4">
      <div class="flex items-center gap-2">
        <label class="mr-2">Tampilkan</label>
        <select v-model="filters.perPage" class="border border-gray-300 rounded px-2 py-1">
          <option v-for="size in [5, 10, 25, 50]" :key="size" :value="size">{{ size }}</option>
        </select>
        <span class="ml-2">data</span>
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
              <th class="border px-4 py-3">Keperluan</th>
              <th class="border px-4 py-3">Jadwal Pinjam</th>
              <th class="border px-4 py-3">Jadwal Kembali</th>
              <th class="border px-4 py-3">Entry User</th>
              <th class="border px-4 py-3">Respon Admin</th>
              <th class="border px-4 py-3">Tanggal Ambil</th>
              <th class="border px-4 py-3">Operator Ambil</th>
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
              <td class="border px-4 py-2">{{ loan.created_by.name }}</td>
              <td class="border px-4 py-3">{{ loan.requester.name }}</td>
              <td class="border px-4 py-3 text-sm text-gray-600">{{ loan.purpose }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.start_at) || '-' }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.end_at) }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.created_at) }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.responded_at) }}</td>
              <td class="border px-4 py-2">{{ formatDate(loan.borrowed_at) || '-' }}</td>
              <td class="border px-4 py-3">{{ loan.checked_out_by?.name || '-' }}</td>
              <!-- <td class="border px-4 py-2">{{ formatDate(loan.returned_at) || '-' }}</td>
              <td class="border px-4 py-3">{{ loan.checked_in_by?.name || '-' }}</td>
              <td class="border px-4 py-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold capitalize" :class="getStatusClass(loan.status)">
                  {{ loan.status.replace(/_/g, ' ') }}
                </span>
              </td> -->
              <td class="border px-4 py-2">
                <div class="flex items-center justify-center gap-4">
                  
                  <template v-if="loan.status === 'APPROVED'">
                    <button @click="checkOut(loan)" title="Serahkan Barang" class="text-green-500 hover:text-green-700 transition" :disabled="processingId === loan.id">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </button>
                    <button @click="openEditModal(loan)" title="Edit Peminjaman" class="text-blue-500 hover:text-blue-700 transition" :disabled="processingId === loan.id">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg>
                    </button>
                    <button @click="cancelLoan(loan)" title="Batalkan Peminjaman" class="text-red-500 hover:text-red-700 transition" :disabled="processingId === loan.id">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </button>
                  </template>

                  <template v-else-if="loan.status === 'ACTIVE'">
                    <button @click="openEditModal(loan)" title="Edit Peminjaman" class="text-blue-500 hover:text-blue-700 transition" :disabled="processingId === loan.id">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg>
                    </button>
                    <button @click="cancelLoan(loan)" title="Batalkan Peminjaman" class="text-red-500 hover:text-red-700 transition" :disabled="processingId === loan.id">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </button>
                  </template>

                  <template v-else>
                    <button @click="showDetails(loan)" title="Lihat Detail" class="text-gray-500 hover:text-gray-700">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      <p v-if="!loading && loans.length === 0" class="text-gray-500 text-center mt-4">Tidak ada data yang cocok dengan filter.</p>
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

  <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-2xl p-6 rounded-lg shadow-lg overflow-y-auto max-h-[90vh]">
      <h2 class="text-xl font-bold mb-4">Edit Detail Peminjaman</h2>
      <form @submit.prevent="submitUpdateLoan" class="space-y-4">
        
        <div class="p-3 bg-gray-100 rounded-md border text-sm">
            <p><strong>Pemohon:</strong> {{ editForm.requester?.name || '...' }}</p>
            <p><strong>Tipe Barang:</strong> <span class="capitalize">{{ editForm.item_type }}</span></p>
        </div>

        <div>
          <label class="block font-medium">Barang yang Ditugaskan</label>
          <select 
              v-model="editForm.item_id" 
              class="w-full border rounded px-3 py-2 bg-white disabled:bg-gray-200 disabled:cursor-not-allowed" 
              :disabled="editForm.status === 'ACTIVE'"
              required>
              <option v-if="availableItemsForEdit.length === 0" disabled value="">Memuat barang...</option>
              <option v-for="item in availableItemsForEdit" :key="item.id" :value="item.id">
                  {{ item.brand }} - (Kode: {{ item.code }}) {{ item.id === originalItemId ? '(Barang saat ini)' : '' }}
              </option>
          </select>
          
          <p v-if="editForm.status === 'ACTIVE'" class="text-xs text-yellow-700 mt-1">
              Barang tidak dapat diganti karena peminjaman sedang aktif (sudah diserahkan).
          </p>

          <p v-if="validationErrors.item_id" class="text-red-500 text-sm mt-1">{{ validationErrors.item_id[0] }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
              <label class="block font-medium">Tgl & Jam Mulai Pinjam</label>
              <input v-model="editForm.start_at" type="datetime-local" class="w-full border rounded px-3 py-2" required />
              <p v-if="validationErrors.start_at" class="text-red-500 text-sm mt-1">{{ validationErrors.start_at[0] }}</p>
          </div>
          <div>
              <label class="block font-medium">Tgl & Jam Selesai Pinjam</label>
              <input v-model="editForm.end_at" type="datetime-local" class="w-full border rounded px-3 py-2" required />
              <p v-if="validationErrors.end_at" class="text-red-500 text-sm mt-1">{{ validationErrors.end_at[0] }}</p>
          </div>
        </div>
            
        <div>
          <label class="block font-medium">Tujuan Peminjaman</label>
          <textarea v-model="editForm.purpose" class="w-full border rounded px-3 py-2" required></textarea>
          <p v-if="validationErrors.purpose" class="text-red-500 text-sm mt-1">{{ validationErrors.purpose[0] }}</p>
        </div>
        
        <div>
          <label class="block font-medium">Lokasi Penggunaan</label>
          <textarea v-model="editForm.location" class="w-full border rounded px-3 py-2" required></textarea>
          <p v-if="validationErrors.location" class="text-red-500 text-sm mt-1">{{ validationErrors.location[0] }}</p>
        </div>

        <div class="mt-6 flex justify-end gap-4">
          <button @click="closeEditModal" type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Batal</button>
          <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
            {{ isSubmitting ? 'Menyimpan...' : 'Update Peminjaman' }}
          </button>
        </div>
      </form>
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
        status: 'APPROVED', 
        search: '',
        page: 1, 
        perPage: 10 
      },
      pagination: {},
      processingId: null,
      tabs: [
        { name: 'Disetujui', status: 'APPROVED' },
        { name: 'Aktif', status: 'ACTIVE' },
        { name: 'Ditolak', status: 'REJECTED' },
        { name: 'Dibatalkan', status: 'CANCELLED' },
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
    };
  },
  watch: {
    'filters.search': _.debounce(function() { this.filters.page = 1; this.fetchLoans(); }, 500),
    'filters.perPage': function() { this.filters.page = 1; this.fetchLoans(); },
    'filters.status': function() { this.filters.page = 1; this.fetchLoans(); },
    'filters.page': function() { this.fetchLoans(); }
  },
  methods: {
    async fetchLoans() {
      this.loading = true;
      try {
        const response = await this.$axios.get('/api/loans', { params: this.filters });
        this.loans = response.data.data;
        this.pagination = response.data;
      } catch (error) {
        this.$swal.fire('Gagal!', 'Tidak dapat mengambil data peminjaman dari server.', 'error');
      } finally {
        this.loading = false;
      }
    },
    filterByStatus(status) {
      this.filters.status = status;
    },
    changePage(page) {
      if (page > 0 && page <= this.pagination.last_page) {
        this.filters.page = page;
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
            APPROVED: 'bg-blue-100 text-blue-800',
            ACTIVE: 'bg-green-100 text-green-800',
            COMPLETED: 'bg-indigo-100 text-indigo-800',
            REJECTED: 'bg-red-100 text-red-800', 
            CANCELLED: 'bg-gray-100 text-gray-800',
        };
        return classes[loan.status] || 'bg-gray-200 text-gray-900';
    },
  },
  created() {
    this.fetchLoans();
  },
};
</script>