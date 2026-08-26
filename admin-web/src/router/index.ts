import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory('/admin/'),
  routes: [
    { path: '/login', component: () => import('../views/Login.vue'), meta: { public: true } },
    {
      path: '/',
      component: () => import('../layout/MainLayout.vue'),
      redirect: '/dashboard',
      children: [
        { path: 'dashboard', component: () => import('../views/Dashboard.vue'), meta: { title: '仪表盘' } },
        { path: 'contents', component: () => import('../views/ContentList.vue'), meta: { title: '内容管理' } },
        { path: 'contents/create', component: () => import('../views/ContentEdit.vue'), meta: { title: '新建内容' } },
        { path: 'contents/edit/:id', component: () => import('../views/ContentEdit.vue'), meta: { title: '编辑内容' } },
        { path: 'categories', component: () => import('../views/CategoryList.vue'), meta: { title: '分类管理' } },
        { path: 'tags', component: () => import('../views/TagList.vue'), meta: { title: '标签管理' } },
        { path: 'card-batches', component: () => import('../views/CardBatchList.vue'), meta: { title: '卡密批次' } },
        { path: 'cards', component: () => import('../views/CardList.vue'), meta: { title: '卡密管理' } },
        { path: 'vip', component: () => import('../views/VipGrant.vue'), meta: { title: 'VIP 发放' } },
        { path: 'invite-codes', component: () => import('../views/InviteCodeList.vue'), meta: { title: '邀请码' } },
        { path: 'users', component: () => import('../views/UserList.vue'), meta: { title: '用户管理' } },
        { path: 'comments', component: () => import('../views/CommentList.vue'), meta: { title: '评论审核' } },
        { path: 'settings', component: () => import('../views/SettingView.vue'), meta: { title: '系统设置' } },
      ],
    },
  ],
})

// 登录守卫
router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.public) {
    return true
  }
  if (!auth.token) {
    return { path: '/login' }
  }
  return true
})

export default router
