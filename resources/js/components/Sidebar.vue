<template>
  <aside class="w-64 bg-white p-6 shadow-md flex flex-col items-center">
    <!-- Logo PCU -->
    <img :src="logo" alt="PCU Logo" class="w-32 h-auto mb-4" />

    <!-- Menu Navigasi -->
    <ul class="w-full space-y-2">
      <li v-for="item in filteredMenuItems" :key="item.name">
        <router-link
          :to="getMenuItemRoute(item)"
          class="flex items-center p-3 hover:bg-gray-200 rounded text-center"
          active-class="bg-gray-300 font-semibold"
        >
          <component :is="item.icon" class="w-5 h-5 mr-2" />
          {{ item.name }}
        </router-link>
      </li>
    </ul>
  </aside>
</template>

<script>
import {
  Squares2X2Icon,
  ClipboardDocumentListIcon,
  ClockIcon,
  ArrowUturnLeftIcon,
  ArchiveBoxIcon,
  ChartBarIcon,
  UserGroupIcon,
  CalendarDaysIcon,
  DocumentChartBarIcon
} from '@heroicons/vue/24/outline';
import { mapGetters } from 'vuex';
import logo from '@/assets/logo-pcu.png';

export default {
  name: 'Sidebar',
  data() {
    return {
      logo,
      // Define menu items per role
      menuItemsByRole: {
        PTIK: [
          { name: 'Dashboard', icon: Squares2X2Icon },
          { name: 'Request', icon: ClipboardDocumentListIcon },
          { name: 'Loan', icon: ClockIcon },
          { name: 'Return', icon: ArrowUturnLeftIcon },
          { name: 'Item', icon: ArchiveBoxIcon },
          { name: 'Prediction', icon: ChartBarIcon },
          { name: 'User', icon: UserGroupIcon },
          { name: 'Schedule', icon: CalendarDaysIcon },
          { name: 'Report', icon: DocumentChartBarIcon }
        ],
        TU: [
          { name: 'Request', icon: ClipboardDocumentListIcon, route: '/tureq' },
          { name: 'Loan', icon: ClockIcon, route: '/tuloan' }, // Example: TU routes to /loan/tu
        ],
        // You can add more roles in the future
        User: [
          { name: 'Request', icon: ClipboardDocumentListIcon, route: '/userreq' },
          { name: 'Loan', icon: ClockIcon, route: '/userloan' }, // Example: TU routes to /loan/tu
        ]
      }
    };
  },
  computed: {
    ...mapGetters(['userRole']),
    filteredMenuItems() {
      if (!this.userRole) return [];
      return this.menuItemsByRole[this.userRole] || [];
    }
  },
  methods: {
    getMenuItemRoute(item) {
      // Use custom route if defined, otherwise fallback to default
      return item.route || `/${item.name.toLowerCase()}`;
    }
  },
  mounted() {
    // Fetch user data if not already loaded
    if (!this.userRole) {
      this.$store.dispatch('fetchUserData');
    }
  }
};
</script>
