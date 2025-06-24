import { createStore } from 'vuex';
import axios from 'axios';

export default createStore({
  state: {
    userRole: null,
    userName: null,  // Menyimpan nama pengguna
  },
  mutations: {
    setUserRole(state, role) {
      state.userRole = role;
    },
    setUserName(state, name) {
      state.userName = name;  // Menyimpan nama pengguna
    },
  },
  actions: {
    async fetchUserData({ commit }) {
      try {
        const response = await axios.get('/api/me');
        commit('setUserRole', response.data.role);  // Simpan role
        commit('setUserName', response.data.name);  // Simpan nama
      } catch (error) {
        console.error('Failed to fetch user data:', error);
      }
    },
  },
  getters: {
    userRole: (state) => state.userRole,
    userName: (state) => state.userName,  // Mengambil nama pengguna dari state
  },
});
