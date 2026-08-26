import axios from 'axios'
import { ElMessage } from 'element-plus'

/** 统一响应结构 */
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
}

const request = axios.create({
  baseURL: '/admin-api',
  timeout: 60000,
})

// 请求拦截：注入 Bearer Token
request.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// 响应拦截：统一解包并处理错误
request.interceptors.response.use(
  (response) => {
    const body = response.data as ApiResponse
    if (body.code !== 0) {
      ElMessage.error(body.message || '请求失败')
      return Promise.reject(new Error(body.message || '请求失败'))
    }
    // 拦截器返回解包后的响应体（类型局部断言，调用方以 ApiResponse<T> 接收）
    return body as unknown as typeof response
  },
  (error) => {
    const status: number | undefined = error.response?.status
    if (status === 401) {
      localStorage.removeItem('admin_token')
      ElMessage.error('登录已过期，请重新登录')
      window.location.href = '/admin/'
    } else {
      ElMessage.error(error.response?.data?.message || '网络错误，请稍后重试')
    }
    return Promise.reject(error)
  },
)

export default request
