import { defineStore } from 'pinia'
import { ref } from 'vue'
import { getMe, login as loginApi, logout as logoutApi } from '../api'

/** 后台认证状态 */
export const useAuthStore = defineStore('auth', () => {
  const token = ref<string>(localStorage.getItem('admin_token') || '')
  const username = ref<string>('')

  async function login(username_: string, password: string): Promise<void> {
    const { data } = await loginApi({ username: username_, password })
    token.value = data.token
    localStorage.setItem('admin_token', data.token)
    await fetchMe()
  }

  async function fetchMe(): Promise<void> {
    const me = await getMe()
    username.value = me.data.username
  }

  async function logout(): Promise<void> {
    try {
      await logoutApi()
    } finally {
      token.value = ''
      localStorage.removeItem('admin_token')
    }
  }

  return { token, username, login, fetchMe, logout }
})
