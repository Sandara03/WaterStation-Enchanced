<template>
  <q-layout
    ><q-page-container
      ><q-page class="login-page flex flex-center q-pa-md">
        <q-card class="login-card q-pa-lg" flat bordered>
          <q-card-section
            ><q-avatar color="blue-1" text-color="primary" icon="water_drop" size="64px" />
            <div class="text-h4 text-weight-bold q-mt-lg">Welcome to AHY</div>
            <p class="text-grey-7 q-mt-sm">
              Water Station · Point of Sale & Management
            </p></q-card-section
          >
          <q-card-section
            ><q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" rounded role="alert">{{
              error
            }}</q-banner>
            <q-form @submit="login" class="q-gutter-md">
              <q-input
                v-model.trim="email"
                outlined
                label="Staff email"
                type="email"
                autocomplete="username"
                :rules="[(v) => !!v || 'Enter your email']"
              />
              <q-input
                v-model="password"
                outlined
                label="Password"
                :type="show ? 'text' : 'password'"
                autocomplete="current-password"
                :rules="[(v) => !!v || 'Enter your password']"
                ><template #append
                  ><q-btn
                    flat
                    round
                    :icon="show ? 'visibility_off' : 'visibility'"
                    aria-label="Toggle password visibility"
                    @click="show = !show" /></template
              ></q-input>
              <q-btn
                type="submit"
                class="full-width"
                unelevated
                color="primary"
                size="lg"
                label="Sign in"
                :loading="busy"
              />
            </q-form>
          </q-card-section>
          <q-card-section v-if="isMobileApp && !fixedMobileServer" class="q-pt-none">
            <q-expansion-item icon="dns" label="Server connection" caption="Connect to your Water Station computer">
              <div class="q-pt-md">
                <q-input v-model.trim="serverAddress" outlined label="API server address" type="url" placeholder="http://192.168.1.10:8000/api" :disable="connecting || busy" />
                <p class="text-caption text-grey-7 q-mt-sm">Keep your phone and computer on the same Wi-Fi, and start Laravel with --host=0.0.0.0. The computer must stay on.</p>
                <q-banner v-if="connectionMessage" dense rounded class="q-mb-sm" :class="connectionOk ? 'bg-green-1 text-positive' : 'bg-red-1 text-negative'" role="status">{{ connectionMessage }}</q-banner>
                <q-btn color="primary" outline label="Test and save connection" :loading="connecting" :disable="busy" @click="connectServer" />
              </div>
            </q-expansion-item>
            <div class="text-caption text-grey-6 text-center q-mt-sm">Android app v{{ appVersion }}</div>
          </q-card-section>
          <q-card-section class="text-caption text-grey-7"
            >Sign in with your administrator or cashier account.</q-card-section
          >
          <q-card-section v-if="showHomeScreenHelp" class="q-pt-none">
            <q-btn flat color="primary" icon="ios_share" label="Add to Home Screen" @click="installHelpOpen = true" />
          </q-card-section>
        </q-card>
        <q-dialog v-model="installHelpOpen">
          <q-card class="q-pa-md" style="width: 420px; max-width: 92vw">
            <q-card-section>
              <div class="text-h6">Install on iPhone</div>
              <p>Open this website in Safari on your iPhone, then:</p>
              <ol class="q-pl-md">
                <li>Tap <strong>Share</strong>.</li>
                <li>Choose <strong>Add to Home Screen</strong>.</li>
                <li>Keep <strong>Open as Web App</strong> on if shown, then tap <strong>Add</strong>.</li>
              </ol>
              <p class="text-caption text-grey-7 q-mb-none">Sales and account access need a connection to the station server. For a local demo, keep your computer on and both devices on the same Wi-Fi. For the hosted website, use your internet connection.</p>
            </q-card-section>
            <q-card-actions align="right"><q-btn flat color="primary" label="Got it" v-close-popup /></q-card-actions>
          </q-card>
        </q-dialog>
      </q-page></q-page-container
    ></q-layout
  >
</template>
<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { CapacitorHttp } from '@capacitor/core';
import { useRoute, useRouter } from 'vue-router';
import { signIn } from '../services/auth';
import api, { errorMessage, isMobileApp, fixedMobileServer, saveMobileServer } from '../services/api';
import { normalizeServerUrl } from '../services/mobileServer';
import { clearSession } from '../services/auth';
const router = useRouter(),
  route = useRoute();
const email = ref(''),
  password = ref(''),
  show = ref(false),
  busy = ref(false);
const error = ref(
  route.query.unavailable
    ? 'Unable to connect to the server. Check that the backend is running, then sign in.'
    : '',
);
const appVersion = process.env.AHY_APP_VERSION;
const installedWebApp = window.matchMedia('(display-mode: standalone)').matches || (navigator as Navigator & { standalone?: boolean }).standalone === true;
const showHomeScreenHelp = ref(!isMobileApp && !installedWebApp);
const installHelpOpen = ref(false);
const serverAddress = ref(String(api.defaults.baseURL || ''));
const connecting = ref(false), connectionMessage = ref(''), connectionOk = ref(false);
async function connectServer() {
  connecting.value = true;
  connectionMessage.value = '';
  connectionOk.value = false;
  try {
    const target = normalizeServerUrl(serverAddress.value);
    const response = isMobileApp
      ? await CapacitorHttp.get({ url: `${target}/health`, connectTimeout: 10000, readTimeout: 10000, responseType: 'json', headers: { Accept: 'application/json' } })
      : await axios.get(`${target}/health`, { timeout: 10000, headers: { Accept: 'application/json' } });
    if (response.status !== 200) throw new Error(`The server returned HTTP ${response.status}. Check that this is your Water Station API address.`);
    const data = response.data;
    if (data?.status !== 'ok') throw new Error('This address did not return a healthy Water Station API.');
    serverAddress.value = saveMobileServer(target);
    clearSession();
    connectionOk.value = true;
    connectionMessage.value = 'Connected. You can sign in now.';
    error.value = '';
  } catch (e) {
    connectionMessage.value = errorMessage(e);
  } finally { connecting.value = false; }
}
async function login() {
  if (busy.value || connecting.value) return;
  if (isMobileApp && !api.defaults.baseURL) {
    error.value = 'Open Server connection and connect to your Water Station computer first.';
    return;
  }
  busy.value = true;
  error.value = '';
  try {
    await signIn(email.value, password.value);
    password.value = '';
    await router.replace('/');
  } catch (e) {
    error.value = errorMessage(e);
  } finally {
    busy.value = false;
  }
}
</script>
<style scoped>
.login-page {
  background: radial-gradient(ellipse at top left, #ceeefe, transparent 60%), #f3f8fc;
}
.login-card {
  width: 440px;
  max-width: 100%;
  border-radius: 24px;
  box-shadow: 0 20px 60px #0b476218;
}
</style>
