import './bootstrap';
import '../css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store'; 
import Swal from 'sweetalert2';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

axios.defaults.baseURL = 'http://localhost:8000';
axios.defaults.withCredentials = true;

const app = createApp(App);

app.config.globalProperties.$axios = axios;

app.use(store);

app.config.globalProperties.$swal = Swal;

app.use(router).mount('#app');

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker
      .register('/service-worker.js')  // Pastikan path ke service-worker di root public/
      .then((registration) => {
        console.log('Service Worker registered with scope:', registration.scope);
      })
      .catch((error) => {
        console.log('Service Worker registration failed:', error);
      });
  });
}

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,           // <-- UBAH INI
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,     // <-- UBAH INI
    forceTLS: true,
    // Jika Anda menggunakan enkripsi, tambahkan baris ini
    // encrypted: true, 
});

window.Echo.channel('loans')
    .listen('.loan.updated', (event) => {
        const loan = event.loan;
        
        console.log(loan); // Lihat data loan di console

        if (loan.status === 'APPROVED') {
            // Update UI untuk approved menggunakan SweetAlert2
            Swal.fire({
                title: 'Permohonan Disetujui!',
                text: 'Permohonan dengan ID ' + loan.id + ' telah disetujui.',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        } else if (loan.status === 'REJECTED') {
            // Update UI untuk declined
            Swal.fire({
                title: 'Permohonan Ditolak!',
                text: 'Permohonan dengan ID ' + loan.id + ' telah ditolak.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else if (loan.status === 'CANCELLED') {
            // Update UI untuk cancelled
            Swal.fire({
                title: 'Permohonan Dibatalkan!',
                text: 'Permohonan dengan ID ' + loan.id + ' telah dibatalkan.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        }
    });