import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 20000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json'
  }
})

api.interceptors.request.use(config => {
  const token = sessionStorage.getItem('ahy_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})
api.interceptors.response.use(response => response, error => {
  if (error.response?.status === 401 && !String(error.config?.url).includes('/auth/login')) {
    sessionStorage.removeItem('ahy_token')
    sessionStorage.removeItem('ahy_cart')
    sessionStorage.removeItem('ahy_checkout')
    if (!location.pathname.endsWith('/login')) location.assign('/login')
  }
  return Promise.reject(error)
})
export function errorMessage(error: any): string {
  const errors = error.response?.data?.errors
  return (errors && Object.values(errors).flat()[0] as string) || error.response?.data?.message || error.message || 'The request failed. Please try again.'
}
export default api
