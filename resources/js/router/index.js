import { createRouter, createWebHistory } from 'vue-router';

import Login from '../views/Login.vue';
import Request from '../views/RequestDashboard.vue';
import Loan from '../views/LoanDashboard.vue';
import Return from '../views/ReturnDashboard.vue';
import Item from '../views/ItemDashboard.vue';
import Prediction from '../views/PredictionDashboard.vue';
import User from '../views/UserDashboard.vue';
import Schedule from '../views/ScheduleDashboard.vue';
import Report from '../views/ReportDashboard.vue';
import TUReq from '../views/TUReqDashboard.vue';
import TULoan from '../views/TULoanDashboard.vue';
import UserReq from '../views/UserReqDashboard.vue';
import UserLoan from '../views/UserLoanDashboards.vue';

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', component: Login },
  { path: '/request', component: Request },
  { path: '/loan', component: Loan },
  { path: '/return', component: Return },
  { path: '/item', component: Item },
  { path: '/prediction', component: Prediction },
  { path: '/user', component: User },
  { path: '/schedule', component: Schedule },
  { path: '/report', component: Report },
  { path: '/tureq', component: TUReq },
  { path: '/tuloan', component: TULoan },
  { path: '/userreq', component: UserReq },
  { path: '/userloan', component: UserLoan },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
