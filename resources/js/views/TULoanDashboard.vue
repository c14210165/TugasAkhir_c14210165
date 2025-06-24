<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar class="w-64 bg-white shadow-md" />

    <!-- Konten Utama -->
    <div class="flex-1 flex flex-col min-h-0">
      <!-- Topbar -->
      <Topbar class="bg-white shadow-md px-6 py-4" />

      <!-- Main Content -->
      <main class="flex-1 p-8 bg-white shadow-md rounded-lg mx-6 my-4 overflow-y-auto min-h-0">
        <h2 class="text-3xl font-semibold mb-6 text-gray-800">Daftar Peminjaman</h2>
        <TULoan />
      </main>
    </div>
  </div>
</template>

<script>
import Topbar from '../components/Topbar.vue';
import Sidebar from '../components/Sidebar.vue';
import TULoan from '../components/TULoan.vue';

export default {
  components: {
    Topbar,
    Sidebar,
    TULoan
  }
};
</script>
