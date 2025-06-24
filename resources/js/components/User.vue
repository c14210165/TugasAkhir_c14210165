<template>
  <div class="p-6 bg-white shadow-md rounded-md">
    <!-- Header dan Kontrol Utama -->
    <div class="flex justify-between items-center mb-4 flex-wrap gap-4">
      <div>
        <button @click="openAddAdminModal" class="px-3 py-1 border border-blue-500 text-blue-500 rounded hover:bg-blue-500 hover:text-white transition">+ Tambah Admin</button>
      </div>
      <div>
        <input type="text" v-model="filters.search" placeholder="Cari admin..." class="border border-gray-300 rounded-md px-3 py-2 w-64" />
      </div>
    </div>

    <!-- Indikator Loading -->
    <div v-if="loading" class="text-center py-10"><p class="text-gray-500">Loading data...</p></div>

    <!-- Tabel Data Admin -->
    <div v-if="!loading" class="overflow-x-auto">
      <table v-if="users.length > 0" class="w-full border border-gray-300 rounded-md">
        <thead>
          <tr class="bg-gray-200 text-gray-700 text-left text-sm">
            <th class="border px-4 py-3">#</th>
            <th class="border px-4 py-3">Nama</th>
            <th class="border px-4 py-3">Username</th>
            <th class="border px-4 py-3">Email</th>
            <th class="border px-4 py-3">Deskripsi</th>
            <th class="border px-4 py-3 text-center">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(user, index) in users" :key="user.id" class="hover:bg-gray-100 transition">
            <td class="border px-4 py-2">{{ pagination.from + index }}</td>
            <td class="border px-4 py-2">{{ user.name }}</td>
            <td class="border px-4 py-2 font-mono">{{ user.username }}</td>
            <td class="border px-4 py-2">{{ user.email }}</td>
            <td class="border px-4 py-2 text-sm text-gray-600">{{ user.description || '-' }}</td>
            <td class="border px-4 py-2">
              <div class="flex items-center justify-center gap-4">
                <button @click="openEditModal(user)" title="Edit User" class="text-blue-500 hover:text-blue-700 transition"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg></button>
                <button @click="demoteUser(user)" title="Cabut Akses Admin" class="text-red-500 hover:text-red-700 transition"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg></button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!loading && users.length === 0" class="text-gray-500 text-center mt-4">Tidak ada data admin yang cocok.</p>
    </div>

    <!-- Paginasi -->
    <div v-if="!loading && pagination.total > 0" class="flex justify-between items-center mt-4 text-sm text-gray-600">
        <div>Menampilkan <span class="font-semibold">{{ pagination.from }}</span> sampai <span class="font-semibold">{{ pagination.to }}</span> dari <span class="font-semibold">{{ pagination.total }}</span> data</div>
        <div class="flex items-center gap-2">
            <button :disabled="!pagination.prev_page_url" @click="changePage(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
            <span>Page {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="changePage(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
        </div>
    </div>
    
    <!-- Modal Tambah / Jadikan Admin -->
    <div v-if="showAddAdminModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white w-full max-w-2xl p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">Tambah / Jadikan Admin</h2>
        
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="activeModalTab = 'promote'" :class="[activeModalTab === 'promote' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">Jadikan Admin User yang Ada</button>
            <button @click="activeModalTab = 'create'" :class="[activeModalTab === 'create' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">Buat Admin Baru</button>
          </nav>
        </div>

        <div v-if="activeModalTab === 'promote'" class="mt-6">
          <label class="block font-medium">Cari User (berdasarkan Nama atau Email)</label>
          <input type="text" v-model="candidateSearch" placeholder="Ketik minimal 3 huruf untuk mencari..." class="w-full border rounded px-3 py-2 mt-1" />
          <div v-if="candidateLoading" class="text-center py-4">Mencari...</div>
          <div v-if="candidateResults.length > 0" class="mt-4 border rounded max-h-48 overflow-y-auto">
            <ul>
              <li v-for="candidate in candidateResults" :key="candidate.id" class="flex justify-between items-center p-3 border-b last:border-b-0">
                <div><p class="font-semibold">{{ candidate.name }}</p><p class="text-sm text-gray-500">{{ candidate.email }} (Role: {{ candidate.role }})</p></div>
                <button @click="promoteUser(candidate)" class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600 transition">Jadikan Admin</button>
              </li>
            </ul>
          </div>
          <p v-if="candidateSearch.length >= 3 && candidateResults.length === 0 && !candidateLoading" class="text-sm text-gray-500 mt-2">User tidak ditemukan.</p>
        </div>

        <div v-if="activeModalTab === 'create'" class="mt-6">
          <form @submit.prevent="submitNewAdmin" class="space-y-4">
            <div><label class="block font-medium">Nama Lengkap</label><input v-model="newAdminForm.name" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.name" class="text-red-500 text-sm mt-1">{{ validationErrors.name[0] }}</p></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div><label class="block font-medium">Username</label><input v-model="newAdminForm.username" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.username" class="text-red-500 text-sm mt-1">{{ validationErrors.username[0] }}</p></div>
              <div><label class="block font-medium">Email</label><input v-model="newAdminForm.email" type="email" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.email" class="text-red-500 text-sm mt-1">{{ validationErrors.email[0] }}</p></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div><label class="block font-medium">Password</label><input v-model="newAdminForm.password" type="password" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.password" class="text-red-500 text-sm mt-1">{{ validationErrors.password[0] }}</p></div>
              <div><label class="block font-medium">Konfirmasi Password</label><input v-model="newAdminForm.password_confirmation" type="password" class="w-full border rounded px-3 py-2" required /></div>
            </div>
            <div><label class="block font-medium">Keterangan / Jabatan</label><textarea v-model="newAdminForm.description" class="w-full border rounded px-3 py-2" required placeholder="Contoh: Staff PTIK Bagian Jaringan"></textarea><p v-if="validationErrors.description" class="text-red-500 text-sm mt-1">{{ validationErrors.description[0] }}</p></div>
            <div class="flex justify-end pt-4"><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Buat Admin Baru</button></div>
          </form>
        </div>
        
        <div class="mt-6 border-t pt-4 flex justify-end"><button @click="closeAddAdminModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Tutup</button></div>
      </div>
    </div>

    <!-- ================================================= -->
    <!-- =============== MODAL EDIT ADMIN =============== -->
    <!-- ================================================= -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white w-full max-w-2xl p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">Edit User Admin</h2>
        <form @submit.prevent="submitUpdateAdmin" class="space-y-4">
          <div><label class="block font-medium">Nama Lengkap</label><input v-model="editForm.name" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.name" class="text-red-500 text-sm mt-1">{{ validationErrors.name[0] }}</p></div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-medium">Username</label><input v-model="editForm.username" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.username" class="text-red-500 text-sm mt-1">{{ validationErrors.username[0] }}</p></div>
            <div><label class="block font-medium">Email</label><input v-model="editForm.email" type="email" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.email" class="text-red-500 text-sm mt-1">{{ validationErrors.email[0] }}</p></div>
          </div>
          <div><label class="block font-medium">Deskripsi</label><textarea v-model="editForm.description" class="w-full border rounded px-3 py-2" required></textarea><p v-if="validationErrors.description" class="text-red-500 text-sm mt-1">{{ validationErrors.description[0] }}</p></div>
          <hr/>
          <div><label class="block font-medium text-gray-500">Ganti Password (opsional)</label><input v-model="editForm.password" type="password" class="w-full border rounded px-3 py-2 mt-1" placeholder="Isi hanya jika ingin ganti password" /><p v-if="validationErrors.password" class="text-red-500 text-sm mt-1">{{ validationErrors.password[0] }}</p></div>
          <div><label class="block font-medium text-gray-500">Konfirmasi Password Baru</label><input v-model="editForm.password_confirmation" type="password" class="w-full border rounded px-3 py-2 mt-1" /></div>
          
          <div class="mt-6 flex justify-end gap-4">
            <button @click="closeEditModal" type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Batal</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update Admin</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script>
import _ from 'lodash';

export default {
  data() {
    return {
      users: [],
      loading: true,
      filters: { search: '', perPage: 10, page: 1 },
      pagination: {},
      
      // State untuk Modal Tambah Admin
      showAddAdminModal: false,
      activeModalTab: 'promote',
      candidateSearch: '',
      candidateResults: [],
      candidateLoading: false,
      newAdminForm: { name: '', username: '', email: '', password: '', password_confirmation: '', description: '' },
      
      // State untuk Modal Edit Admin
      showEditModal: false,
      editForm: {}, 
      editingUserId: null,

      validationErrors: {},
    };
  },
  watch: {
    'filters.search': _.debounce(function() { this.filters.page = 1; this.fetchUsers(); }, 500),
    'filters.perPage': function() { this.filters.page = 1; this.fetchUsers(); },
    
    candidateSearch: _.debounce(async function(newValue) {
      if (newValue.length < 3) { this.candidateResults = []; return; }
      this.candidateLoading = true;
      try {
        const response = await this.$axios.get(`/api/users/search?search=${newValue}`);
        this.candidateResults = response.data;
      } catch (error) { this.candidateResults = []; } 
      finally { this.candidateLoading = false; }
    }, 500),
  },
  methods: {
    async fetchUsers() {
      this.loading = true;
      try {
        const response = await this.$axios.get('/api/users', { params: this.filters });
        this.users = response.data.data;
        this.pagination = response.data;
      } catch (error) { this.$swal.fire('Gagal!', 'Tidak dapat mengambil data admin dari server.', 'error'); } 
      finally { this.loading = false; }
    },
    changePage(page) {
      if (page > 0 && page <= this.pagination.last_page) {
        this.filters.page = page;
        this.fetchUsers();
      }
    },
    
    openAddAdminModal() {
      this.activeModalTab = 'promote';
      this.candidateSearch = '';
      this.candidateResults = [];
      this.validationErrors = {};
      this.newAdminForm = { name: '', username: '', email: '', password: '', password_confirmation: '', description: '' };
      this.showAddAdminModal = true;
    },
    closeAddAdminModal() { this.showAddAdminModal = false; },
    
    async promoteUser(candidate) {
      const confirmResult = await this.$swal.fire({
        title: `Jadikan ${candidate.name} Admin?`, text: `User ini akan mendapatkan hak akses sebagai PTIK.`,
        icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Lanjutkan', cancelButtonText: 'Batal',
      });
      if (confirmResult.isConfirmed) {
        const { value: description } = await this.$swal.fire({
          title: 'Masukkan Keterangan', input: 'text', inputLabel: `Keterangan/Jabatan untuk ${candidate.name}`,
          inputPlaceholder: 'Contoh: Staff Magang PTIK', showCancelButton: true,
          inputValidator: (value) => { if (!value) { return 'Keterangan tidak boleh kosong!' } }
        });
        if (description) {
          try {
            await this.$axios.post(`/api/users/${candidate.id}/promote`, { _method: 'PUT', description: description });
            this.closeAddAdminModal();
            await this.fetchUsers();
            this.$swal.fire('Berhasil!', `${candidate.name} sekarang adalah admin.`, 'success');
          } catch (error) { this.$swal.fire('Gagal!', 'Terjadi kesalahan saat mempromosikan user.', 'error'); }
        }
      }
    },
    async submitNewAdmin() {
      this.validationErrors = {};
      try {
        await this.$axios.post('/api/users', this.newAdminForm);
        this.closeAddAdminModal();
        await this.fetchUsers();
        this.$swal.fire('Berhasil!', 'Admin baru berhasil dibuat.', 'success');
      } catch (error) {
        if (error.response && error.response.status === 422) { this.validationErrors = error.response.data.errors; } 
        else { this.$swal.fire('Gagal!', 'Terjadi kesalahan saat membuat admin baru.', 'error'); }
      }
    },
    
    openEditModal(user) {
      this.validationErrors = {};
      this.editingUserId = user.id;
      this.editForm = { ...user };
      this.editForm.password = '';
      this.editForm.password_confirmation = '';
      this.showEditModal = true;
    },
    closeEditModal() {
      this.showEditModal = false;
    },
    async submitUpdateAdmin() {
      this.validationErrors = {};
      try {
        await this.$axios.post(`/api/users/${this.editingUserId}`, { ...this.editForm, _method: 'PUT' });
        this.closeEditModal();
        await this.fetchUsers();
        this.$swal.fire('Berhasil!', 'Data admin telah diupdate.', 'success');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.validationErrors = error.response.data.errors;
        } else {
          this.$swal.fire('Gagal!', 'Terjadi kesalahan saat mengupdate.', 'error');
        }
      }
    },
    async demoteUser(user) {
      const result = await this.$swal.fire({
        title: `Cabut Akses Admin?`, text: `User "${user.name}" akan dikembalikan menjadi user biasa dan keterangannya akan dihapus.`,
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, Cabut Akses!', cancelButtonText: 'Batal',
      });
      if (result.isConfirmed) {
        try {
          const response = await this.$axios.post(`/api/users/${user.id}/demote`, { _method: 'DELETE' });
          await this.fetchUsers();
          this.$swal.fire('Berhasil!', response.data.message, 'success');
        } catch (error) {
          const message = error.response?.data?.message || 'Terjadi kesalahan.';
          this.$swal.fire('Gagal!', message, 'error');
        }
      }
    },
  },
  created() {
    this.fetchUsers();
  },
};
</script>