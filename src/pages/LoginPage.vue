<template>
  <q-layout><q-page-container><q-page class="login-page flex flex-center q-pa-md">
    <q-card class="login-card q-pa-lg" flat bordered>
      <q-card-section><q-avatar color="blue-1" text-color="primary" icon="water_drop" size="64px"/><div class="text-h4 text-weight-bold q-mt-lg">Welcome to AHY</div><p class="text-grey-7 q-mt-sm">Water Station · Point of Sale & Management</p></q-card-section>
      <q-card-section><q-banner v-if="error" class="bg-red-1 text-negative q-mb-md" rounded role="alert">{{ error }}</q-banner>
        <q-form @submit="login" class="q-gutter-md">
          <q-input v-model.trim="email" outlined label="Staff email" type="email" autocomplete="username" :rules="[v => !!v || 'Enter your email']"/>
          <q-input v-model="password" outlined label="Password" :type="show ? 'text' : 'password'" autocomplete="current-password" :rules="[v => !!v || 'Enter your password']"><template #append><q-btn flat round :icon="show ? 'visibility_off' : 'visibility'" aria-label="Toggle password visibility" @click="show = !show"/></template></q-input>
          <q-btn type="submit" class="full-width" unelevated color="primary" size="lg" label="Sign in" :loading="busy"/>
        </q-form>
      </q-card-section>
      <q-card-section class="text-caption text-grey-7">Use the staff account created during setup. Ask your project administrator if you need access.</q-card-section>
    </q-card>
  </q-page></q-page-container></q-layout>
</template>
<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { signIn } from '../services/auth'
import { errorMessage } from '../services/api'
const router = useRouter(), route = useRoute()
const email = ref(''), password = ref(''), show = ref(false), busy = ref(false)
const error = ref(route.query.unavailable ? 'Unable to connect to the server. Check that the backend is running, then sign in.' : '')
async function login() {
  if (busy.value) return
  busy.value = true; error.value = ''
  try { await signIn(email.value, password.value); password.value = ''; await router.replace('/') }
  catch (e) { error.value = errorMessage(e) }
  finally { busy.value = false }
}
</script>
<style scoped>
.login-page { background: radial-gradient(ellipse at top left, #ceeefe, transparent 60%), #f3f8fc; }
.login-card { width: 440px; max-width: 100%; border-radius: 24px; box-shadow: 0 20px 60px #0b476218; }
</style>
