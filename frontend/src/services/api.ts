import axios from 'axios';
import { normalizeServerUrl } from './mobileServer';

export const isMobileApp = process.env.MODE === 'capacitor';
export const defaultMobileServer = process.env.AHY_API_URL || '';
let mobileServer = defaultMobileServer;
if (isMobileApp) {
  try { mobileServer = localStorage.getItem('ahy_api_server') || defaultMobileServer; } catch { /* Use the build default if device storage is unavailable. */ }
}

const api = axios.create({
  baseURL: isMobileApp ? mobileServer : import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 20000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

export function saveMobileServer(input: string) {
  const target = normalizeServerUrl(input);
  if (sessionStorage.getItem('ahy_checkout')) {
    throw new Error('Resolve the pending checkout on the original server before changing servers.');
  }
  localStorage.setItem('ahy_api_server', target);
  sessionStorage.removeItem('ahy_token');
  sessionStorage.removeItem('ahy_cart');
  api.defaults.baseURL = target;
  return target;
}

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('ahy_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && !String(error.config?.url).includes('/auth/login')) {
      sessionStorage.removeItem('ahy_token');
      sessionStorage.removeItem('ahy_cart');
      sessionStorage.removeItem('ahy_checkout');
      if (!location.pathname.endsWith('/login')) location.assign('/login');
    }
    return Promise.reject(error);
  },
);
export function errorMessage(error: any): string {
  if (isMobileApp && !error.response && (error.code === 'ERR_NETWORK' || error.code === 'ECONNABORTED' || /network|timed?\s*out|timeout/i.test(error.message || ''))) {
    return 'Cannot reach the Water Station server. Open Server connection, check the API address, and test it in Chrome on this phone. Keep Laravel running and both devices on the same Wi-Fi.';
  }
  const errors = error.response?.data?.errors;
  return (
    (errors && (Object.values(errors).flat()[0] as string)) ||
    error.response?.data?.message ||
    error.message ||
    'The request failed. Please try again.'
  );
}
export default api;
