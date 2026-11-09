import test from 'node:test';
import assert from 'node:assert/strict';
import {
  orderTotal,
  paymentReady,
  persistState,
  restoreCart,
  restorePending,
} from '../src/services/checkoutState.ts';

const product = {
  id: 1,
  name: 'Purified water',
  price: '25.00',
  stock: 10,
  low_stock: 3,
  is_active: true,
};
const cart = [{ product, quantity: 2 }];
const pending = {
  checkout_key: '00000000-0000-4000-8000-000000000001',
  customer_id: null,
  customer_name: 'Walk-in Customer',
  payment_method: 'Cash',
  cash_received: 100,
  payment_reference: null,
  expected_total: 50,
  items: [{ product_id: 1, quantity: 2 }],
};

test('a saved checkout keeps the confirmed total after product prices change', () => {
  const restored = restorePending(JSON.stringify(pending));
  assert.equal(orderTotal([{ product: { ...product, price: 30 }, quantity: 2 }], restored), 50);
  assert.equal(orderTotal([], restored), 50);
  assert.equal(restored.checkout_key, pending.checkout_key);
});
test('older valid checkout requests retain the key and omit the newer total field', () => {
  const { expected_total, ...older } = pending;
  const restored = restorePending(JSON.stringify(older));
  assert.equal(Object.hasOwn(restored, 'expected_total'), false);
  assert.equal(restored.checkout_key, older.checkout_key);
  assert.equal(orderTotal(cart, restored), 50);
});
test('malformed saved checkout data cannot become a request', () => {
  for (const value of [
    'broken',
    'true',
    '[]',
    JSON.stringify({ ...pending, checkout_key: 'bad' }),
    JSON.stringify({ ...pending, items: [] }),
    JSON.stringify({ ...pending, cash_received: 50.001 }),
  ])
    assert.equal(restorePending(value), null);
});
test('invalid cart rows do not break recovery of the valid rows', () => {
  const recovered = restoreCart([
    null,
    {},
    ...cart,
    { product, quantity: 0 },
    { product: { ...product, price: -25 }, quantity: 1 },
    { product: { ...product, stock: 'many' }, quantity: 1 },
  ]);
  assert.deepEqual(recovered, cart);
});
test('cash must be sufficient and have no more than two decimal places', () => {
  assert.equal(paymentReady(50, 100, 'Cash', '', false), true);
  assert.equal(paymentReady(50, 50, 'Cash', '', false), true);
  for (const amount of [null, NaN, Infinity, 49.99, 50.001, '100'])
    assert.equal(paymentReady(50, amount, 'Cash', '', false), false);
});
test('digital payments require exact amount, reference, and fresh confirmation', () => {
  for (const method of ['GCash', 'Bank Transfer']) {
    assert.equal(paymentReady(50, 50, method, 'DEMO-REF', true), true);
    assert.equal(paymentReady(50, 50, method, 'DEMO-REF', false), false);
    assert.equal(paymentReady(50, 50, method, ' ', true), false);
    assert.equal(paymentReady(50, 51, method, 'DEMO-REF', true), false);
    assert.equal(paymentReady(50, 49, method, 'DEMO-REF', true), false);
  }
  assert.equal(paymentReady(50, 50, 'Other', 'DEMO-REF', true), false);
});
test('cart totals use integer centavos for fractional product prices', () => {
  assert.equal(orderTotal([{ product: { ...product, price: '0.10' }, quantity: 3 }]), 0.3);
  assert.equal(orderTotal([{ product: { ...product, price: '19.99' }, quantity: 3 }]), 59.97);
});
test('checkout persistence reports failure instead of pretending the retry key was saved', () => {
  let saved;
  assert.equal(
    persistState(
      {
        setItem: (key, value) => {
          saved = { key, value };
        },
      },
      'ahy_checkout',
      pending,
    ),
    true,
  );
  assert.equal(JSON.parse(saved.value).checkout_key, pending.checkout_key);
  assert.equal(
    persistState(
      {
        setItem: () => {
          throw new Error('Storage unavailable');
        },
      },
      'ahy_checkout',
      pending,
    ),
    false,
  );
});
