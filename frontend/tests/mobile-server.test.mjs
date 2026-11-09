import { test } from 'node:test';
import assert from 'node:assert/strict';
import { normalizeServerUrl } from '../src/services/mobileServer.ts';

test('phone server accepts private Wi-Fi and emulator hosts and normalizes the API path', () => {
  assert.equal(normalizeServerUrl(' http://192.168.254.156:8000/ '), 'http://192.168.254.156:8000/api');
  assert.equal(normalizeServerUrl('http://10.0.2.2:8000/api/'), 'http://10.0.2.2:8000/api');
  assert.equal(normalizeServerUrl('http://172.16.2.3:8000/api'), 'http://172.16.2.3:8000/api');
  assert.equal(normalizeServerUrl('https://station.example.test/api/'), 'https://station.example.test/api');
});

test('phone server rejects loopback, public HTTP, URL credentials, and active schemes', () => {
  for (const url of ['http://localhost:8000/api', 'https://127.0.0.1/api', 'http://[::1]/api',
    'http://public.example.test/api', 'http://172.32.1.1/api', 'https://user:secret@station.example.test/api',
    'javascript:alert(1)', 'file:///api', 'https://station.example.test/api?token=secret',
    'https://station.example.test/api#token', 'not a URL']) {
    assert.throws(() => normalizeServerUrl(url), url);
  }
});
