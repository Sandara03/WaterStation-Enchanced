export const money = (value: number | string | undefined) => Number(value || 0).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
export const localDate = (value = new Date()) => `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
export const dateTime = (value: string) => value ? new Date(value).toLocaleString('en-PH', { timeZone: 'Asia/Manila', dateStyle: 'medium', timeStyle: 'short' }) : '—'
