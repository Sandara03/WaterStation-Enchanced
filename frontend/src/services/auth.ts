import { computed, ref } from 'vue'
import api from './api'

export interface Staff { id: number; name: string; email: string; role: 'admin' | 'cashier' }
export const staff = ref<Staff | null>(null)
export const isAdmin = computed(() => staff.value?.role === 'admin')
let checked = false
export async function restoreSession() {
  if (checked) return !!staff.value
  if (!sessionStorage.getItem('ahy_token')) { checked = true; return false }
  try { staff.value = (await api.get('/auth/me')).data; checked = true; return true }
  catch (error: any) {
    if (error.response?.status === 401) { clearSession(); return false }
    throw error
  }
}
export async function signIn(email: string, password: string) {
  const { data } = await api.post('/auth/login', { email, password })
  sessionStorage.setItem('ahy_token', data.token)
  staff.value = data.user
  checked = true
}
export function clearSession() {
  sessionStorage.removeItem('ahy_token')
  sessionStorage.removeItem('ahy_cart')
  sessionStorage.removeItem('ahy_checkout')
  staff.value = null
  checked = false
}
export async function signOut() {
  // Keep the session if the server cannot revoke it; allow the user to retry.
  await api.post('/auth/logout')
  clearSession()
}
