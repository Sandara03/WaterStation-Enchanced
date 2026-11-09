import { defineConfig } from '#q-app/wrappers'
import packageInfo from './package.json'

export default defineConfig(() => ({
  css: ['app.scss'],

  extras: ['material-icons'],

  build: {
    vueRouterMode: 'history',
    publicPath: process.env.AHY_COMBINED_BUILD === '1' ? '/app/' : '/',
    vueRouterBase: '/',
    env: {
      AHY_API_URL: process.env.AHY_API_URL || '',
      AHY_APP_VERSION: packageInfo.version,
    },
  },

  devServer: {
    host: '127.0.0.1',
    port: 9000,
    open: false,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },

  framework: {
    config: {},
    plugins: ['Dialog', 'Notify'],
  },

  capacitor: {
    hideSplashscreen: true,
  },
}))
