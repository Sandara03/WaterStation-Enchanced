<template>
  <q-dialog
    :model-value="modelValue"
    persistent
    @update:model-value="(v) => $emit('update:modelValue', v)"
  >
    <q-card class="form-dialog"
      ><q-card-section class="row justify-between items-center"
        ><h2 class="section-heading">{{ customer?.id ? 'Edit customer' : 'New customer' }}</h2>
        <q-btn
          flat
          round
          icon="close"
          aria-label="Close customer form"
          :disable="saving"
          @click="$emit('update:modelValue', false)"
      /></q-card-section>
      <q-form @submit="save"
        ><q-card-section class="q-gutter-md q-pt-none">
          <q-banner v-if="error" rounded class="bg-red-1 text-negative" role="alert">{{
            error
          }}</q-banner>
          <q-input
            v-model.trim="form.name"
            outlined
            label="Full name"
            maxlength="255"
            :rules="[(v) => !!v?.trim() || 'Name is required']"
            autofocus
          />
          <q-input
            v-model.trim="form.phone"
            outlined
            label="Phone"
            type="tel"
            maxlength="40"
            autocomplete="tel"
          />
          <q-input
            v-model.trim="form.email"
            outlined
            label="Email (optional)"
            type="email"
            maxlength="255"
            autocomplete="email"
          />
          <q-input
            v-model.trim="form.address"
            outlined
            label="Address"
            maxlength="255"
            autocomplete="street-address"
            hint="Used to fill delivery details when this customer makes a purchase."
          />
          <q-input
            v-model.trim="form.notes"
            outlined
            type="textarea"
            label="Customer notes"
            maxlength="2000"
            autogrow
          /> </q-card-section
        ><q-card-actions align="right" class="q-pa-md"
          ><q-btn
            flat
            label="Cancel"
            :disable="saving"
            @click="$emit('update:modelValue', false)" /><q-btn
            color="primary"
            label="Save customer"
            type="submit"
            :loading="saving" /></q-card-actions
      ></q-form>
    </q-card>
  </q-dialog>
</template>
<script setup lang="ts">
import { ref, watch } from 'vue';
import api, { errorMessage } from '../services/api';
const props = defineProps<{ modelValue: boolean; customer?: any }>(),
  emit = defineEmits(['update:modelValue', 'saved']);
const blank = () => ({ name: '', phone: '', email: '', address: '', notes: '' }),
  form = ref(blank()),
  saving = ref(false),
  error = ref('');
watch(
  () => props.modelValue,
  (v) => {
    if (v) {
      form.value = {
        ...blank(),
        ...Object.fromEntries(
          Object.keys(blank()).map((key) => [key, props.customer?.[key] || '']),
        ),
      };
      error.value = '';
    }
  },
);
async function save() {
  if (saving.value) return;
  saving.value = true;
  error.value = '';
  try {
    const { data } = props.customer?.id
      ? await api.patch(`/customers/${props.customer.id}`, form.value)
      : await api.post('/customers', form.value);
    emit('saved', data);
    emit('update:modelValue', false);
  } catch (e) {
    error.value = errorMessage(e);
  } finally {
    saving.value = false;
  }
}
</script>
