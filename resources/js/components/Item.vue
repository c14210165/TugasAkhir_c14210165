<template>
  <div class="p-6 bg-white shadow-md rounded-md">
    <div class="flex items-center justify-between border-b mb-4">
      <div class="flex">
        <button @click="filterByType('Semua')" :class="filters.type === 'Semua' ? 'border-b-4 border-blue-500 text-blue-600' : 'text-gray-600'" class="px-6 py-3 hover:text-blue-500 transition capitalize">Semua</button>
        
        <button v-for="tab in itemTypeOptions" :key="tab.value" @click="filterByType(tab.value)"
                :class="filters.type === tab.value ? 'border-b-4 border-blue-500 text-blue-600' : 'text-gray-600'"
                class="px-6 py-3 hover:text-blue-500 transition capitalize">
          {{ tab.label }}
        </button>
      </div>
      <div>
        <button @click="openAddTypeModal"
                class="px-3 py-1 mr-2 border border-purple-500 text-purple-500 rounded hover:bg-purple-500 hover:text-white transition">
          + Tambah Tipe Barang
        </button>
        <button @click="openAddModal" class="px-3 py-1 border border-blue-500 text-blue-500 rounded hover:bg-blue-500 hover:text-white transition">+ Tambah Barang</button>
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
        <input type="text" v-model="filters.search" placeholder="Cari brand, kode, barcode..." class="border border-gray-300 rounded px-3 py-1 w-64" />
      </div>
    </div>

    <div v-if="loading" class="text-center py-10"><p class="text-gray-500">Loading data...</p></div>

    <div v-if="!loading" class="overflow-x-auto">
      <table v-if="items.length > 0" class="w-full border border-gray-300 rounded-md">
        <thead>
            <tr class="bg-gray-200 text-gray-700 text-left text-sm">
                <th class="border px-4 py-3">#</th>
                <th class="border px-4 py-3">Kode Barang</th>
                <th class="border px-4 py-3">Barcode</th>
                <th class="border px-4 py-3">Brand</th>
                <th class="border px-4 py-3">Tipe</th>
                <th class="border px-4 py-3">Jenis</th>
                <th class="border px-4 py-3">Kelengkapan</th>
                <th class="border px-4 py-3">Status</th>
                <th class="border px-4 py-3">Status Pinjam</th>
                <th class="border px-4 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(item, index) in items" :key="item.id" class="hover:bg-gray-100 transition">
                <td class="border px-4 py-2">{{ pagination.from + index }}</td>
                <td class="border px-4 py-2 font-mono">{{ item.code }}</td>
                <td class="border px-4 py-2 font-mono">{{ item.barcode }}</td>
                <td class="border px-4 py-2">{{ item.brand }}</td>
                <td class="border px-4 py-2 capitalize">{{ item.name }}</td>
                <td class="border px-4 py-2 capitalize">{{ item.item_type.name }}</td>
                <td class="border px-4 py-2 text-sm text-gray-600">{{ item.accessories || '-' }}</td>
                <td class="border px-4 py-2">
                    <span :class="getStatusClass(item.status)" 
                          class="px-2 py-1 rounded-full text-xs font-semibold">
                        {{ item.status.replace('_', ' ') }}
                    </span>
                </td>
                <td class="border border-black px-4 py-2 text-center text-sm" :class="{'text-red-600 font-semibold': item.is_late}">
                    {{ item.lateness_info }}
                </td>
                <td class="border px-4 py-2">
                    <div class="flex items-center justify-center gap-4">
                        <button 
                            @click="openEditModal(item)" 
                            :disabled="item.status === 'BORROWED'"
                            title="Edit Barang" 
                            class="text-blue-500 hover:text-blue-700 transition disabled:text-gray-300 disabled:cursor-not-allowed">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" /><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" /></svg>
                        </button>
                        <button 
                            @click="hapusBarang(item.id)" 
                            :disabled="item.status === 'BORROWED'"
                            title="Hapus Barang" 
                            class="text-red-500 hover:text-red-700 transition disabled:text-gray-300 disabled:cursor-not-allowed">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        </button>
                    </div>
                </td>
            </tr>
        </tbody>
      </table>
      <p v-if="items.length === 0" class="text-gray-500 text-center mt-4">Tidak ada data yang cocok.</p>
    </div>

    <div v-if="!loading && pagination.total > 0" class="flex justify-between items-center mt-4 text-sm text-gray-600">
      <div>Menampilkan <span class="font-semibold">{{ pagination.from }}</span> sampai <span class="font-semibold">{{ pagination.to }}</span> dari <span class="font-semibold">{{ pagination.total }}</span> data</div>
      <div class="flex items-center gap-2">
        <button :disabled="!pagination.prev_page_url" @click="changePage(pagination.current_page - 1)" class="px-3 py-1 border rounded disabled:opacity-50">Prev</button>
        <span>Page {{ pagination.current_page }} dari {{ pagination.last_page }}</span>
        <button :disabled="!pagination.next_page_url" @click="changePage(pagination.current_page + 1)" class="px-3 py-1 border rounded disabled:opacity-50">Next</button>
      </div>
    </div>
    
    <div v-if="showAddModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">Tambah Barang Baru</h2>
        <form @submit.prevent="submitNewItem" class="space-y-4">
          <div><label class="block font-medium">Brand</label><input v-model="addForm.brand" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.brand" class="text-red-500 text-sm mt-1">{{ validationErrors.brand[0] }}</p></div>
          <div><label class="block font-medium">Nama Barang</label><input v-model="addForm.name" type="text" class="w-full border rounded px-3 py-2" required placeholder="Contoh: Inspiron 14, Proyektor XYZ, dll." /><p v-if="validationErrors.name" class="text-red-500 text-sm mt-1">{{ validationErrors.name[0] }}</p></div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-medium">Kode Barang</label><input v-model="addForm.code" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.code" class="text-red-500 text-sm mt-1">{{ validationErrors.code[0] }}</p></div>
            <div><label class="block font-medium">Barcode</label><input v-model="addForm.barcode" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.barcode" class="text-red-500 text-sm mt-1">{{ validationErrors.barcode[0] }}</p></div>
          </div>
          <div>
            <label class="block font-medium">Tipe Barang</label>
            <select v-model="addForm.item_type_id" class="w-full border rounded px-3 py-2 bg-white" required> <option disabled value="">Pilih tipe...</option>
                <option v-for="type in itemTypeOptions" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
            </select>
            <p v-if="validationErrors.item_type_id" class="text-red-500 text-sm mt-1">{{ validationErrors.item_type_id[0] }}</p>
          </div>
          <div><label class="block font-medium">Kelengkapan</label><textarea v-model="addForm.accessories" class="w-full border rounded px-3 py-2"></textarea></div>
          <div class="mt-6 flex justify-end gap-4">
            <button @click="closeAddModal" type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Batal</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan Barang</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white w-full max-w-lg p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">Edit Barang</h2>
        <form @submit.prevent="submitUpdateItem" class="space-y-4">
          <div><label class="block font-medium">Brand</label><input v-model="editForm.brand" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.brand" class="text-red-500 text-sm mt-1">{{ validationErrors.brand[0] }}</p></div>
          <div><label class="block font-medium">Nama Barang</label><input v-model="editForm.name" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.name" class="text-red-500 text-sm mt-1">{{ validationErrors.name[0] }}</p></div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label class="block font-medium">Kode Barang</label><input v-model="editForm.code" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.code" class="text-red-500 text-sm mt-1">{{ validationErrors.code[0] }}</p></div>
            <div><label class="block font-medium">Barcode</label><input v-model="editForm.barcode" type="text" class="w-full border rounded px-3 py-2" required /><p v-if="validationErrors.barcode" class="text-red-500 text-sm mt-1">{{ validationErrors.barcode[0] }}</p></div>
          </div>
          <div>
            <label class="block font-medium">Tipe Barang</label>
            <select v-model="editForm.item_type_id" class="w-full border rounded px-3 py-2 bg-white" required> <option disabled value="">Pilih tipe...</option>
                <option v-for="type in itemTypeOptions" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
            </select>
            <p v-if="validationErrors.item_type_id" class="text-red-500 text-sm mt-1">{{ validationErrors.item_type_id[0] }}</p>
          </div>
          <div>
              <label class="block font-medium">Status Barang</label>
              <select 
                  v-model="editForm.status" 
                  class="w-full border rounded px-3 py-2 bg-white disabled:bg-gray-200 disabled:cursor-not-allowed"
                  :disabled="editForm.current_loan !== null">
                  <option v-for="st in ['AVAILABLE', 'BORROWED', 'IN_MAINTENANCE']" :key="st" :value="st">{{ st }}</option>
              </select>
              <p v-if="editForm.current_loan" class="text-xs text-yellow-700 bg-yellow-50 p-2 rounded-md mt-2">
                  Status tidak bisa diubah karena barang ini terikat pada permohonan yang sedang berjalan.
              </p>
              <p v-if="validationErrors.status" class="text-red-500 text-sm mt-1">{{ validationErrors.status[0] }}</p>
          </div>
          <div><label class="block font-medium">kelengkapan</label><textarea v-model="editForm.accessories" class="w-full border rounded px-3 py-2"></textarea></div>
          <div class="mt-6 flex justify-end gap-4">
            <button @click="closeEditModal" type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Batal</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update Barang</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal tambah tipe barang -->
    <div v-if="showAddTypeModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white w-full max-w-sm p-6 rounded-lg shadow-lg">
        <h2 class="text-lg font-bold mb-4">Tambah Tipe Barang</h2>
        <form @submit.prevent="submitNewType">
          <div>
            <label class="block font-medium">Nama Tipe Barang</label>
            <input v-model="addTypeForm.name" type="text" class="w-full border rounded px-3 py-2" required />
            <p v-if="validationErrors.name" class="text-red-500 text-sm mt-1">{{ validationErrors.name[0] }}</p>
          </div>
          <div class="mt-6 flex justify-end gap-4">
            <button @click="showAddTypeModal = false" type="button" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Batal</button>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Simpan</button>
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
      items: [],
      loading: true,
      filters: { type: 'Semua', search: '', perPage: 10, page: 1 },
      pagination: {},
      showAddModal: false,
      addForm: { brand: '', name: '', code: '', barcode: '', item_type_id: '', accessories: '', status: 'AVAILABLE' },
      showEditModal: false,
      editForm: {},
      editingItemId: null,
      validationErrors: {},
      // [DITAMBAHKAN] State untuk menampung pilihan tipe barang dari API
      itemTypeOptions: [],
      showAddTypeModal: false,
      addTypeForm: { name: '' },
    };
  },
  watch: {
    'filters.search': _.debounce(function() { this.filters.page = 1; this.fetchItems(); }, 500),
    'filters.perPage': function() { this.filters.page = 1; this.fetchItems(); },
    'filters.type': function() { this.filters.page = 1; this.fetchItems(); },
  },
  methods: {
    async fetchItems() {
      this.loading = true;
      try {
        let apiParams = { ...this.filters };
        if (apiParams.type === 'Semua') { delete apiParams.type; }
        const response = await this.$axios.get('/api/items', { params: apiParams });
        this.items = response.data.data;
        this.pagination = response.data;
      } catch (error) {
        console.error("Gagal mengambil data barang:", error);
        this.$swal.fire('Gagal!', 'Tidak dapat mengambil data barang dari server.', 'error');
      } finally {
        this.loading = false;
      }
    },
    // [DITAMBAHKAN] Method baru untuk mengambil daftar tipe barang
    async fetchItemTypes() {
        try {
            const response = await this.$axios.get('/api/items/types');
            this.itemTypeOptions = response.data;
        } catch (error) {
            console.error("Gagal mengambil pilihan tipe barang:", error);
            this.$swal.fire('Peringatan', 'Gagal memuat filter tipe barang dari server.', 'warning');
        }
    },
    openAddTypeModal() {
      this.addTypeForm.name = '';
      this.validationErrors = {};
      this.showAddTypeModal = true;
    },
    closeAddTypeModal() {
      this.showAddTypeModal = false;
    },
    async submitNewType() {
      this.validationErrors = {};
      try {
        await this.$axios.post('/api/types/add', this.addTypeForm);
        this.closeAddTypeModal();
        await this.fetchItemTypes(); // Refresh tipe barang di list
        this.$swal.fire('Berhasil!', 'Tipe barang baru ditambahkan.', 'success');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          // Contoh error { errors: { label: [...], value: [...] } }
          this.validationErrors.typeLabel = error.response.data.errors.label || [];
          this.validationErrors.typeValue = error.response.data.errors.value || [];
        } else {
          this.$swal.fire('Gagal!', 'Terjadi kesalahan saat menambah tipe.', 'error');
        }
      }
    },
    changePage(page) {
      if (page > 0 && page <= this.pagination.last_page) {
        this.filters.page = page;
        this.fetchItems();
      }
    },
    filterByType(type) {
      this.filters.type = type;
      // Watcher akan otomatis memanggil fetchItems()
    },
    openAddModal() {
      this.validationErrors = {};
      this.addForm = { brand: '', name: '', code: '', barcode: '', type: '', accessories: '', status: 'AVAILABLE' };
      this.showAddModal = true;
    },
    closeAddModal() { this.showAddModal = false; },
    async submitNewItem() {
      this.validationErrors = {};
      try {
        await this.$axios.post('/api/items', this.addForm);
        this.closeAddModal();
        await this.fetchItems();
        this.$swal.fire('Berhasil!', 'Barang baru telah ditambahkan.', 'success');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.validationErrors = error.response.data.errors;
        } else {
          this.$swal.fire('Gagal!', 'Terjadi kesalahan saat menyimpan.', 'error');
        }
      }
    },
    openEditModal(item) {
      this.validationErrors = {};
      this.editingItemId = item.id;
      this.editForm = { ...item }; 
      this.showEditModal = true;
    },
    closeEditModal() { this.showEditModal = false; },
    async submitUpdateItem() {
      this.validationErrors = {};
      try {
        await this.$axios.put(`/api/items/${this.editingItemId}`, this.editForm);
        this.closeEditModal();
        await this.fetchItems();
        this.$swal.fire('Berhasil!', 'Data barang telah diupdate.', 'success');
      } catch (error) {
        if (error.response && error.response.status === 422) {
          this.validationErrors = error.response.data.errors;
        } else {
          this.$swal.fire('Gagal!', 'Terjadi kesalahan saat mengupdate.', 'error');
        }
      }
    },
    async hapusBarang(id) {
      const result = await this.$swal.fire({
        title: 'Anda Yakin?',
        text: "Data yang dihapus tidak bisa dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
      });

      if (result.isConfirmed) {
        try {
          await this.$axios.delete(`/api/items/${id}`);
          await this.fetchItems();
          this.$swal.fire('Dihapus!', 'Data barang berhasil dihapus.', 'success');
        } catch (error) {
          this.$swal.fire('Gagal!', 'Terjadi kesalahan saat menghapus data.', 'error');
        }
      }
    },
    getStatusClass(status) {
        const classes = {
            'AVAILABLE': 'bg-green-100 text-green-800',
            'BORROWED': 'bg-orange-100 text-orange-800',
            'IN_MAINTENANCE': 'bg-gray-200 text-gray-800',
        };
        return classes[status] || 'bg-red-100 text-red-800'; 
    },
  },
  created() {
    this.fetchItems();
    this.fetchItemTypes();
  },
};
</script>