import { createRouter, createWebHistory } from 'vue-router'
import DashboardHome from '../pages/DashboardHome.vue'
import ApiExplorerPage from '../pages/ApiExplorerPage.vue'

export const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/_dashboard/', name: 'home', component: DashboardHome },
        { path: '/api/', name: 'api', component: ApiExplorerPage },
    ]
})
