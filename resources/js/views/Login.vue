<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
      <div class="flex justify-center mb-6">
        <img :src="logo" alt="Logo PCU" class="h-20" />
      </div>

      <form @submit.prevent="login">
        <div class="mb-4">
          <label class="block text-gray-700">Email atau Username</label>
          <input
            v-model="form.login"           
            type="text"                     
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring"
            placeholder="Masukkan email atau username Anda"
            required
          />
        </div>

        <div class="mb-6">
          <label class="block text-gray-700">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full px-4 py-2 border rounded focus:outline-none focus:ring"
            placeholder="Masukkan password Anda"
            required
          />
        </div>

        <div v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition"
        >
          {{ isLoading ? 'Loading...' : 'Login' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
// Jika axios global, baris ini bisa dihapus
// import axios from 'axios'; 
import logoPcu from '@/assets/logo-pcu.png';

export default {
  name: 'LoginPage',
  data() {
    return {
      form: {
        login: '', // <-- DIUBAH: dari 'email' menjadi 'login'
        password: '',
      },
      error: null,
      logo: logoPcu,
      isLoading: false,
    };
  },
  methods: {
    async login() {
      this.isLoading = true;
      this.error = null;
      try {
        // Step 1: Ambil CSRF cookie
        await this.$axios.get('/sanctum/csrf-cookie');

        // Step 2: Kirim login dengan this.form yang sekarang berisi 'login' dan 'password'
        const response = await this.$axios.post('/login', this.form);

        // Step 3: Redirect (asumsi Anda pakai Vue Router)
        this.$router.push(response.data.redirect_to);

      } catch (error) {
        if (error.response && error.response.data.message) {
          this.error = error.response.data.message;
        } else {
          this.error = 'Terjadi kesalahan jaringan atau server.';
        }
      } finally {
        this.isLoading = false;
      }
    },
  },
};
</script>