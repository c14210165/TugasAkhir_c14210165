<template>
  <div class="p-6 bg-white shadow-md rounded-md">
    <div class="flex justify-between items-center mb-4">
      <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button v-for="tab in tabs" :key="tab.status" @click="filterByStatus(tab.status)"
                :class="filters.status === tab.status ? 'border-b-4 border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 capitalize">
          {{ tab.name }}
        </button>
      </nav>
      </div>
        <div>
            <button @click="openNewRequestModal"
                class="px-3 py-1 border border-blue-500 text-blue-500 rounded hover:bg-blue-500 hover:text-white transition">
              + Buat Permohonan
            </button>
        </div>
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

    <div v-if="loading" class="text-center py-16"><p class="text-gray-500">Memuat data permohonan...</p></div>

    <div v-if="!loading" class="overflow-x-auto">
        <table v-if="requests.length > 0" class="w-full border border-gray-300 rounded-md">
          <thead>
            <tr class="bg-gray-200 text-gray-700 text-left text-sm">
              <th class="border px-4 py-3">#</th>
              <!-- <th class="border px-4 py-3">Unit/Jurusan</th> -->
              <th class="border px-4 py-3">Pemohon</th>
              <th class="border px-4 py-3">Tipe Barang</th>
              <th class="border px-4 py-3">Keperluan</th>
              <th class="border px-4 py-3">Lokasi</th>
              <th class="border px-4 py-3">Jadwal Pinjam</th>
              <th class="border px-4 py-3">Jadwal Kembali</th>
              <!-- <th class="border px-4 py-3">Entry User</th>
              <th class="border px-4 py-3">Status</th> -->
              <th class="border px-4 py-3 text-center">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(request, index) in requests" :key="request.id" class="hover:bg-gray-50 transition">
              <td class="border px-4 py-2">{{ pagination.from + index }}</td>
              <!-- <td class="border px-4 py-3">{{ request.created_by.name }}</td> -->
              <td class="border px-4 py-3">{{ request.requester.name }}</td>
              <td class="border px-4 py-3 capitalize">{{ request.item_type }}</td>
              <td class="border px-4 py-3 text-sm text-gray-600">{{ request.purpose }}</td>
              <td class="border px-4 py-3 text-sm text-gray-600">{{ request.location }}</td>
              <td class="border px-4 py-3">{{ formatDate(request.start_at) }}</td>
              <td class="border px-4 py-3">{{ formatDate(request.end_at) }}</td>
              <!-- <td class="border px-4 py-3">{{ formatDate(request.created_at) }}</td>
              <td class="border px-4 py-2">
                <span class="px-2 py-1 rounded-full text-xs font-semibold" :class="getStatusClass(request.status)">
                  {{ request.status.replace('_', ' ') }}
                </span>
              </td> -->
              <td class="border px-4 py-2">
                <div class="flex items-center justify-center gap-4">
                    
                    <template v-if="filters.status === 'PENDING_UNIT'">
                        <template v-if="canBeProcessed(request)">
                            <button @click="openEditModal(request)" title="Edit Permohonan" class="text-blue-500 hover:text-blue-700 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg>
                            </button>
                            <button @click="approveRequest(request)" :disabled="processingId === request.id" title="Approve" class="text-green-500 hover:text-green-700 transition disabled:text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </button>
                            <button @click="declineRequest(request)" :disabled="processingId === request.id" title="Decline" class="text-red-500 hover:text-red-700 transition disabled:text-gray-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            </button>
                        </template>
                        <button v-if="canBeCancelled(request)" @click="cancelRequest(request)" :disabled="processingId === request.id" title="Cancel Permohonan" class="text-gray-500 hover:text-gray-700 transition disabled:text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </button>
                    </template>

                    <template v-else>
                        <button @click="showDetails(request)" title="Lihat Detail" class="text-gray-500 hover:text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z" /><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                        </button>
                    </template>
                    
                </div>
            </td>
            </tr>
          </tbody>
        </table>
      <p v-if="!loading && requests.length === 0" class="text-gray-500 text-center mt-4">
        Tidak ada permohonan yang sesuai dengan filter.
      </p>
      <div v-if="!loading && pagination.total > 0" class="flex justify-between items-center mt-4 text-sm text-gray-600">
      <div>
        Menampilkan <span class="font-semibold">{{ pagination.from }}</span> sampai <span class="font-semibold">{{ pagination.to }}</span> dari <span class="font-semibold">{{ pagination.total }}</span> data
      </div>
      <div class="flex items-center gap-2">
        <button :disabled="!pagination.prev_page_url" @click="changePage(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
        <span>Page {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
        <button :disabled="!pagination.next_page_url" @click="changePage(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
      </div>
    </div>
    </div>
  </div>

  <div v-if="showAddModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-2xl p-6 rounded-lg shadow-lg">
      <h2 class="text-xl font-bold mb-4">Buat Permohonan Peminjaman</h2>
      <form @submit.prevent="submitNewRequest" class="space-y-4">
        <div>
          <label class="block font-medium">Pemohon (Atas Nama)</label>
          <select v-model="addForm.requester_id" class="w-full border rounded px-3 py-2 bg-white" required>
              <option disabled value="">Pilih User Pemohon...</option>
              <option v-for="user in userSelectionList" :key="user.id" :value="user.id">{{ user.name }}</option>
          </select>
          <p v-if="validationErrors.requester_id" class="text-red-500 text-sm mt-1">{{ validationErrors.requester_id[0] }}</p>
        </div>
        <div>
          <label class="block font-medium">Tipe Barang</label>
          <select v-model="addForm.item_type" class="w-full border rounded px-3 py-2 bg-white" required>
              <option disabled value="">Pilih Tipe Barang...</option>
              <option v-for="type in itemTypeOptions" :key="type.value" :value="type.value">{{ type.label }}</option>
          </select>
            <p v-if="validationErrors.item_type" class="text-red-500 text-sm mt-1">{{ validationErrors.item_type[0] }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
              <label class="block font-medium">Tgl & Jam Mulai Pinjam</label>
              <input v-model="addForm.start_at" type="datetime-local" class="w-full border rounded px-3 py-2" required />
              <p v-if="validationErrors.start_at" class="text-red-500 text-sm mt-1">{{ validationErrors.start_at[0] }}</p>
          </div>
          <div>
              <label class="block font-medium">Tgl & Jam Selesai Pinjam</label>
              <input v-model="addForm.end_at" type="datetime-local" class="w-full border rounded px-3 py-2" required />
              <p v-if="validationErrors.end_at" class="text-red-500 text-sm mt-1">{{ validationErrors.end_at[0] }}</p>
          </div>
          </div>
          <div><label class="block font-medium">Tujuan Peminjaman</label><textarea v-model="addForm.purpose" class="w-full border rounded px-3 py-2" required></textarea><p v-if="validationErrors.purpose" class="text-red-500 text-sm mt-1">{{ validationErrors.purpose[0] }}</p></div>
          <div><label class="block font-medium">Lokasi Penggunaan</label><textarea v-model="addForm.location" class="w-full border rounded px-3 py-2" required></textarea><p v-if="validationErrors.location" class="text-red-500 text-sm mt-1">{{ validationErrors.location[0] }}</p></div>
          <div class="mt-6 flex justify-end gap-4">
            <button @click="closeAddModal" type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Batal</button>
            <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">
              {{ isSubmitting ? 'Menyimpan...' : 'Ajukan Permohonan' }}
            </button>
          </div>
        </form>
      </div>
  </div>

  <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-2xl p-6 rounded-lg shadow-lg">
      <h2 class="text-xl font-bold mb-4">Edit Permohonan Peminjaman</h2>
      <form @submit.prevent="submitUpdateRequest" class="space-y-4">
        
        <div><label class="block font-medium">Pemohon (Atas Nama)</label><select v-model="editForm.requester_id" class="w-full border rounded px-3 py-2 bg-white" required><option disabled value="">Pilih User Pemohon...</option><option v-for="user in userSelectionList" :key="user.id" :value="user.id">{{ user.name }}</option></select><p v-if="validationErrors.requester_id" class="text-red-500 text-sm mt-1">{{ validationErrors.requester_id[0] }}</p></div>
        <div><label class="block font-medium">Tipe Barang</label><select v-model="editForm.item_type" class="w-full border rounded px-3 py-2 bg-white" required><option disabled value="">Pilih Tipe Barang...</option><option v-for="type in itemTypeOptions" :key="type.value" :value="type.value">{{ type.label }}</option></select><p v-if="validationErrors.item_type" class="text-red-500 text-sm mt-1">{{ validationErrors.item_type[0] }}</p></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div><label class="block font-medium">Tgl & Jam Mulai Pinjam</label><input v-model="editForm.start_at" type="datetime-local" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.start_at" class="text-red-500 text-sm mt-1">{{ validationErrors.start_at[0] }}</p></div>
          <div><label class="block font-medium">Tgl & Jam Selesai Pinjam</label><input v-model="editForm.end_at" type="datetime-local" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.end_at" class="text-red-500 text-sm mt-1">{{ validationErrors.end_at[0] }}</p></div>
        </div>
        <div><label class="block font-medium">Tujuan Peminjaman</label><textarea v-model="editForm.purpose" class="w-full border rounded px-3 py-2" required></textarea><p v-if="validationErrors.purpose" class="text-red-500 text-sm mt-1">{{ validationErrors.purpose[0] }}</p></div>
        <div><label class="block font-medium">Lokasi Penggunaan</label><textarea v-model="editForm.location" class="w-full border rounded px-3 py-2" required></textarea><p v-if="validationErrors.location" class="text-red-500 text-sm mt-1">{{ validationErrors.location[0] }}</p></div>

        <div class="mt-6 flex justify-end gap-4">
          <button @click="closeEditModal" type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Batal</button>
          <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">{{ isSubmitting ? 'Menyimpan...' : 'Update Permohonan' }}</button>
        </div>
      </form>
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
  </div>

</template>

<script>
import _ from 'lodash';

export default {
  data() {
    return {
      requests: [],
      loading: true,
      filters: {
        status: 'PENDING_UNIT',
        type: 'Semua',
        search: '',
        perPage: 10,
        page: 1,
      },
      pagination: {},
      currentUser: null,
      processingId: null,
      tabs: [
        { name: 'Belum Diproses', status: 'PENDING_UNIT' },
        { name: 'Menunggu Konfirmasi PTIK', status: 'PENDING_PTIK' },
        { name: 'Ditolak', status: 'REJECTED' },
        { name: 'Dibatalkan', status: 'CANCELLED' },
      ],
      
      // State untuk Modal Tambah
      showAddModal: false,
      isSubmitting: false,
      addForm: {
        requester_id: '', item_type: '', location: '',
        purpose: '', start_at: '', end_at: '',
      },
      userSelectionList: [],
      itemTypeOptions: [],
      validationErrors: {},

      // ===================================
      // === STATE BARU untuk Modal Assign ===
      // ===================================
      showAssignModal: false,
      isAssigning: false,
      loadingAvailableItems: false,
      requestToAssign: null,    // Menyimpan data permohonan yang akan di-assign
      availableItems: [],       // Menyimpan daftar item dari API
      selectedItemId: null,     // v-model untuk dropdown assign item
      showEditModal: false,
      editForm: {},
      editingRequestId: null,
    };
  },
  
  watch: {
    'filters.status'() { this.filters.page = 1; this.fetchRequests(); },
    'filters.type'() { this.filters.page = 1; this.fetchRequests(); },
    'filters.search': _.debounce(function() { this.filters.page = 1; this.fetchRequests(); }, 500),
    'filters.perPage'() { this.filters.page = 1; this.fetchRequests(); },
  },
  methods: {
    async fetchRequests() {
      this.loading = true;
      try {
        let apiParams = { ...this.filters };
        if (apiParams.type === 'Semua') {
          delete apiParams.type;
        }
        const response = await this.$axios.get('/api/requests', { params: apiParams });
        this.requests = response.data.data;
        this.pagination = response.data;
      } catch (error) {
        this.$swal.fire('Gagal!', 'Tidak dapat mengambil data permohonan.', 'error');
      } finally {
        this.loading = false;
      }
    },
    async fetchCurrentUser() {
        try {
            const response = await this.$axios.get('/api/me');
            this.currentUser = response.data;
        } catch (error) {
            console.error("Gagal mengambil data user login:", error);
        }
    },
    canBeProcessed(request) {
        if (!this.currentUser) return false;
        const userRole = this.currentUser.role;
        const requestStatus = request.status;
        if (userRole === 'TU' && requestStatus === 'PENDING_UNIT') return true;
        if (userRole === 'PTIK' && requestStatus === 'PENDINGPTIK') return true;
        // Fast-track untuk PTIK
        if (userRole === 'PTIK' && requestStatus === 'PENDING_UNIT' && request.created_by.role === 'PTIK') return true;
        return false;
    },
    canBeCancelled(request) {
        if (!this.currentUser) return false;
        return this.currentUser.id === request.created_by.id && 
                (request.status === 'PENDING_UNIT' || request.status === 'PENDINGPTIK');
    },

    // =========================================================
    // === METHOD APPROVE YANG DIPERBARUI ===
    // =========================================================
    async approveRequest(request) {
      // Jika user adalah PTIK dan status butuh pemilihan barang
      if (this.currentUser.role === 'PTIK') {
          // 1. Simpan data request yang sedang diproses
          this.requestToAssign = request;
          // 2. Reset state modal sebelumnya
          this.selectedItemId = null;
          this.availableItems = [];
          // 3. Buka modal assign
          this.showAssignModal = true;
          // 4. Ambil data barang yang tersedia berdasarkan tipe barang di request
          this.fetchAvailableItems(request.item_type);
      } else {
          // Logika lama (untuk persetujuan TU atau fast-track PTIK yang tidak butuh pilih barang)
          const result = await this.$swal.fire({
              title: 'Setujui Permohonan?',
              text: `Anda akan meneruskan permohonan dari ${request.requester.name} ke tahap selanjutnya.`,
              icon: 'question',
              showCancelButton: true,
              confirmButtonColor: '#3085d6',
              cancelButtonColor: '#d33',
              confirmButtonText: 'Ya, Setujui!',
              cancelButtonText: 'Batal',
          });

          if (result.isConfirmed) {
              this.processingId = request.id;
              try {
                  // Panggil API approve tanpa mengirim item_id
                  await this.$axios.post(`/api/requests/${request.id}/approve`);
                  this.$swal.fire('Berhasil!', 'Permohonan telah disetujui.', 'success');
                  this.fetchRequests(); // Refresh data
              } catch (error) {
                  const message = error.response?.data?.message || 'Terjadi kesalahan.';
                  this.$swal.fire('Gagal!', message, 'error');
              } finally {
                  this.processingId = null;
              }
          }
      }
    },

    // =========================================================
    // === METHOD BARU untuk mengambil data item yang tersedia ===
    // =========================================================
    async fetchAvailableItems(itemType) {
        this.loadingAvailableItems = true;
        try {
            const response = await this.$axios.get('/api/items/available', {
                params: { type: itemType }
            });
            this.availableItems = response.data;
        } catch (error) {
            console.error("Gagal mengambil data barang yang tersedia:", error);
            this.$swal.fire('Gagal!', 'Tidak dapat memuat daftar barang yang tersedia.', 'error');
            this.closeAssignModal(); // Tutup modal jika gagal
        } finally {
            this.loadingAvailableItems = false;
        }
    },

    // ==============================================================
    // === METHOD BARU untuk konfirmasi modal dan mengirim API assign ===
    // ==============================================================
    async confirmAndAssignItem() {
        if (!this.selectedItemId) {
            this.$swal.fire('Peringatan', 'Anda harus memilih satu barang untuk dialokasikan.', 'warning');
            return;
        }

        this.isAssigning = true;
        this.processingId = this.requestToAssign.id; // tandai sedang diproses di tabel

        try {
            // Panggil API approve dengan menyertakan item_id dari dropdown
            await this.$axios.post(`/api/requests/${this.requestToAssign.id}/approve`, {
                item_id: this.selectedItemId
            });

            this.$swal.fire('Berhasil!', 'Permohonan telah disetujui dan barang telah dialokasikan.', 'success');
            this.closeAssignModal();
            this.fetchRequests(); // Refresh tabel

        } catch (error) {
            // Handle jika ada error validasi dari backend (misal item tiba-tiba sudah dipinjam)
            const message = error.response?.data?.message || 'Terjadi kesalahan saat menyetujui.';
            const errors = error.response?.data?.errors;
             if (errors && errors.item_id) {
                this.$swal.fire('Gagal!', errors.item_id[0], 'error');
            } else {
                this.$swal.fire('Gagal!', message, 'error');
            }
        } finally {
            this.isAssigning = false;
            this.processingId = null;
        }
    },

    // =========================================================
    // === HELPER METHOD untuk menutup & mereset modal assign ===
    // =========================================================
    closeAssignModal() {
        this.showAssignModal = false;
        this.requestToAssign = null;
        this.availableItems = [];
        this.selectedItemId = null;
        this.isAssigning = false;
    },

    async declineRequest(request) {
        const { value: reason } = await this.$swal.fire({
            title: 'Tolak Permohonan',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan (Opsional)', // Mengubah label
            inputPlaceholder: 'Tuliskan alasan penolakan di sini...',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tolak',
            confirmButtonColor: '#d33',
            // inputValidator: (value) => { // Baris ini dan isinya dihapus
            //   if (!value) {
            //     return 'Anda harus menuliskan alasan penolakan!'
            //   }
            // }
        });

        // Cek apakah pengguna menekan "Ya, Tolak" atau mengetik sesuatu
        if (reason !== undefined) { // Menggunakan undefined untuk membedakan antara batal dan tidak mengisi
            this.processingId = request.id;
            try {
                // Mengirim alasan penolakan, bisa kosong jika pengguna tidak mengisinya
                await this.$axios.post(`/api/requests/${request.id}/decline`, { rejection_reason: reason || null });
                this.$swal.fire('Ditolak!', 'Permohonan telah ditolak.', 'success');
                this.fetchRequests();
            } catch (error) {
                this.$swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
            } finally {
                this.processingId = null;
            }
        }
    },
    async cancelRequest(request) {
        const result = await this.$swal.fire({
            title: 'Batalkan Permohonan?', text: "Permohonan yang sudah dibatalkan tidak bisa dikembalikan.",
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Batalkan!',
        });
        if (result.isConfirmed) {
            this.processingId = request.id;
            try {
                await this.$axios.post(`/api/requests/${request.id}/cancel`);
                this.$swal.fire('Dibatalkan!', 'Permohonan telah dibatalkan.', 'success');
                this.fetchRequests();
            } catch (error) {
                const message = error.response?.data?.message || 'Terjadi kesalahan.';
                this.$swal.fire('Gagal!', message, 'error');
            } finally {
                this.processingId = null;
            }
        }
    },
    filterByType(type) {
      this.filters.type = type;
    },
    filterByStatus(status) {
      this.filters.status = status;
    },
    openNewRequestModal() {
      this.validationErrors = {};
      this.addForm = { 
        requester_id: '', item_type: '', location: '', 
        purpose: '', start_at: '', end_at: '' 
      };
      this.showAddModal = true;
    },
    closeAddModal() {
      this.showAddModal = false;
    },
    async fetchUserSelectionList() {
        try {
            const response = await this.$axios.get('/api/users/selection-list');
            this.userSelectionList = response.data;
        } catch (error) {
            console.error("Gagal mengambil daftar user:", error);
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
    async submitNewRequest() {
      this.isSubmitting = true;
      this.validationErrors = {};
      try {
        await this.$axios.post('/api/loans', this.addForm);
        this.closeAddModal();
        await this.fetchRequests();
        this.$swal.fire('Berhasil', 'Permohonan baru telah berhasil diajukan.', 'success');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.validationErrors = error.response.data.errors;
        } else {
          this.$swal.fire('Gagal', 'Terjadi kesalahan saat mengajukan permohonan.', 'error');
        }
      } finally {
        this.isSubmitting = false;
      }
    },
    formatDate(dateString) {
      const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
      return new Date(dateString).toLocaleDateString('id-ID', options);
    },
    getStatusClass(status) {
        const map = {
            PENDING_UNIT: 'bg-yellow-100 text-yellow-800',
            PENDINGPTIK: 'bg-blue-100 text-blue-800',
            REJECTED: 'bg-red-100 text-red-800',
            CANCELLED: 'bg-gray-100 text-gray-800',
        };
        return map[status] || 'bg-gray-200 text-gray-900';
    },
    formatDateForInput(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        // Tambah offset timezone manual jika perlu, atau pastikan server & client di timezone yang sama
        // Ini adalah contoh sederhana, mungkin perlu penyesuaian
        const pad = (num) => num.toString().padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    },

    /**
     * Membuka modal edit dan mengisi form dengan data yang dipilih.
     */
    openEditModal(request) {
      this.validationErrors = {};
      this.editingRequestId = request.id;

      // Salin data ke form, dan format tanggalnya
      this.editForm = {
        ...request,
        start_at: this.formatDateForInput(request.start_at),
        end_at: this.formatDateForInput(request.end_at),
      };
      
      this.showEditModal = true;
    },

    closeEditModal() {
      this.showEditModal = false;
    },

    async submitUpdateRequest() {
      this.isSubmitting = true;
      this.validationErrors = {};
      try {
        // Kirim request PUT (atau POST dengan _method spoofing)
        await this.$axios.put(`/api/requests/${this.editingRequestId}`, this.editForm);

        this.closeEditModal();
        await this.fetchRequests(); // Refresh tabel
        this.$swal.fire('Berhasil', 'Permohonan berhasil diupdate.', 'success');

      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.validationErrors = error.response.data.errors;
        } else {
          this.$swal.fire('Gagal', 'Terjadi kesalahan saat mengupdate permohonan.', 'error');
        }
      } finally {
        this.isSubmitting = false;
      }
    },
    changePage(page) {
      if (page > 0 && page <= this.pagination.last_page) {
        this.filters.page = page;
        // this.fetchRequests(); // Tidak perlu, sudah ada di watcher
      }
    },
  },
  created() {
    this.fetchRequests();
    this.fetchCurrentUser();
    this.fetchItemTypeOptions();
    this.fetchUserSelectionList();
  },
};
</script>