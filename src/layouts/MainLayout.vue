<template>
  <q-layout view="lHh Lpr lFf">
    <q-header bordered class="bg-white text-dark"><q-toolbar style="min-height:70px">
      <q-btn flat round icon="menu" aria-label="Toggle navigation" @click="drawer=!drawer"/>
      <q-toolbar-title class="text-weight-bold">AHY <span class="text-grey-6 text-body2 gt-xs">/ Water Station</span></q-toolbar-title>
      <q-badge v-if="!online" color="negative" label="Offline" class="q-mr-md"/>
      <q-btn flat no-caps icon="account_circle" :label="staff?.name" to="/account"/>
      <q-btn flat round icon="logout" aria-label="Sign out" :loading="busy" @click="logout"><q-tooltip>Sign out</q-tooltip></q-btn>
    </q-toolbar></q-header>
    <q-drawer v-model="drawer" show-if-above bordered :width="250">
      <div class="q-pa-lg"><q-avatar color="primary" text-color="white" icon="water_drop"/><div class="text-h6 text-weight-bold q-mt-sm">AHY Water Station</div><div class="text-caption text-grey-7">POINT OF SALE & MANAGEMENT</div></div>
      <q-list padding class="q-px-sm"><q-item v-for="item in visibleNav" :key="item.to" clickable v-ripple :to="item.to" exact active-class="bg-blue-1 text-primary" class="q-mb-xs" style="border-radius:10px"><q-item-section avatar><q-icon :name="item.icon"/></q-item-section><q-item-section>{{ item.label }}</q-item-section></q-item></q-list>
      <div class="q-pa-lg text-caption text-grey-7"><q-badge color="blue-1" text-color="primary">{{ staff?.role === 'admin' ? 'Administrator' : 'Cashier' }}</q-badge><p class="q-mt-md">Clean water. Clear records.</p></div>
    </q-drawer>
    <q-page-container><q-banner v-if="!online" class="bg-orange-1 text-orange-10">You are offline. Reconnect before saving transactions.</q-banner><router-view/></q-page-container>
  </q-layout>
</template>
<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'; import { useRouter } from 'vue-router'; import { useQuasar } from 'quasar'; import { staff,isAdmin,signOut } from '../services/auth'; import { errorMessage } from '../services/api'
const drawer=ref(false),busy=ref(false),online=ref(navigator.onLine),router=useRouter(),q=useQuasar()
const nav=[{label:'Overview',to:'/',icon:'dashboard'},{label:'Point of Sale',to:'/pos',icon:'point_of_sale'},{label:'Products',to:'/products',icon:'water_drop',admin:true},{label:'Inventory',to:'/inventory',icon:'inventory_2'},{label:'Sales & Receipts',to:'/sales',icon:'receipt_long'},{label:'Customers',to:'/customers',icon:'groups'},{label:'Deliveries',to:'/deliveries',icon:'local_shipping'},{label:'Reports',to:'/reports',icon:'insights',admin:true},{label:'Expenses',to:'/expenses',icon:'account_balance_wallet',admin:true},{label:'Activity Log',to:'/audit',icon:'history',admin:true}]
const visibleNav=computed(()=>nav.filter(x=>!x.admin||isAdmin.value))
const connectivity=()=>{online.value=navigator.onLine}
onMounted(()=>{window.addEventListener('online',connectivity);window.addEventListener('offline',connectivity)})
onUnmounted(()=>{window.removeEventListener('online',connectivity);window.removeEventListener('offline',connectivity)})
async function logout(){busy.value=true;try{await signOut();await router.replace('/login')}catch(e){q.notify({type:'negative',message:errorMessage(e)})}finally{busy.value=false}}
</script>
