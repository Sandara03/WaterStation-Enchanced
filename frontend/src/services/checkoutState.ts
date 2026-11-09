export interface Product {
  id: number;
  name: string;
  description?: string;
  price: number | string;
  stock: number;
  low_stock: number;
  is_active: boolean;
}
export interface Item {
  product: Product;
  quantity: number;
}
export interface PendingCheckout {
  checkout_key: string;
  customer_id: number | null;
  customer_name: string | null;
  payment_method: 'Cash' | 'GCash' | 'Bank Transfer';
  cash_received: number;
  payment_reference: string | null;
  expected_total?: number;
  items: { product_id: number; quantity: number }[];
}
const record = (value: unknown): value is Record<string, any> =>
  !!value && typeof value === 'object' && !Array.isArray(value);
const positiveInteger = (value: unknown): value is number =>
  typeof value === 'number' && Number.isInteger(value) && value > 0;
export function validAmount(value: unknown): value is number {
  return (
    typeof value === 'number' &&
    Number.isFinite(value) &&
    value >= 0 &&
    value <= 99999999.99 &&
    Math.abs(value * 100 - Math.round(value * 100)) < 0.000001
  );
}
export function restoreCart(value: unknown): Item[] {
  if (!Array.isArray(value)) return [];
  return value.filter((item): item is Item => {
    if (!record(item) || !record(item.product)) return false;
    const p = item.product;
    return (
      positiveInteger(item.quantity) &&
      item.quantity <= 100000 &&
      positiveInteger(p.id) &&
      typeof p.name === 'string' &&
      p.name.length <= 255 &&
      (typeof p.price === 'number' || (typeof p.price === 'string' && p.price.trim() !== '')) &&
      validAmount(Number(p.price)) &&
      typeof p.stock === 'number' &&
      Number.isInteger(p.stock) &&
      p.stock >= 0 &&
      typeof p.is_active === 'boolean'
    );
  });
}
export function restorePending(value: string | null): PendingCheckout | null {
  if (!value) return null;
  try {
    const p: unknown = JSON.parse(value);
    if (
      !record(p) ||
      typeof p.checkout_key !== 'string' ||
      !/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i.test(p.checkout_key)
    )
      return null;
    if (
      !['Cash', 'GCash', 'Bank Transfer'].includes(p.payment_method) ||
      !validAmount(p.cash_received)
    )
      return null;
    if (p.expected_total !== undefined && !validAmount(p.expected_total)) return null;
    if (p.customer_id != null && !positiveInteger(p.customer_id)) return null;
    if (
      p.customer_name != null &&
      (typeof p.customer_name !== 'string' || p.customer_name.length > 255)
    )
      return null;
    if (
      p.payment_reference != null &&
      (typeof p.payment_reference !== 'string' || p.payment_reference.length > 100)
    )
      return null;
    if (p.payment_method !== 'Cash' && !p.payment_reference?.trim()) return null;
    if (
      !Array.isArray(p.items) ||
      !p.items.length ||
      p.items.length > 100 ||
      !p.items.every(
        (i: unknown) =>
          record(i) &&
          positiveInteger(i.product_id) &&
          positiveInteger(i.quantity) &&
          i.quantity <= 100000,
      )
    )
      return null;
    const result: PendingCheckout = {
      checkout_key: p.checkout_key,
      customer_id: p.customer_id ?? null,
      customer_name: p.customer_name ?? null,
      payment_method: p.payment_method,
      cash_received: p.cash_received,
      payment_reference: p.payment_reference ?? null,
      items: p.items.map((i: any) => ({ product_id: i.product_id, quantity: i.quantity })),
    };
    if (p.expected_total !== undefined) result.expected_total = p.expected_total;
    return result;
  } catch {
    return null;
  }
}
export function orderTotal(cart: Item[], pending: PendingCheckout | null = null): number {
  if (pending?.expected_total !== undefined) return pending.expected_total;
  return (
    cart.reduce((sum, i) => sum + Math.round(Number(i.product.price) * 100) * i.quantity, 0) / 100
  );
}
export function paymentReady(
  total: number,
  amount: unknown,
  method: string,
  reference: string,
  confirmed: boolean,
): boolean {
  if (!validAmount(total) || !validAmount(amount)) return false;
  if (method === 'Cash') return Math.round(amount * 100) >= Math.round(total * 100);
  return (
    ['GCash', 'Bank Transfer'].includes(method) &&
    confirmed &&
    !!reference.trim() &&
    Math.round(amount * 100) === Math.round(total * 100)
  );
}
export function persistState(
  storage: { setItem: (key: string, value: string) => void },
  key: string,
  value: unknown,
): boolean {
  try {
    storage.setItem(key, JSON.stringify(value));
    return true;
  } catch {
    return false;
  }
}
