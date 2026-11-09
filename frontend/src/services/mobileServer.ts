export function normalizeServerUrl(input: string): string {
  let url: URL;
  try {
    url = new URL(input.trim());
  } catch {
    throw new Error('Enter a complete server address, for example http://192.168.1.10:8000/api.');
  }
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password || url.search || url.hash) {
    throw new Error('Use an HTTP or HTTPS address without credentials, query parameters, or a fragment.');
  }
  if (url.hostname === 'localhost' || url.hostname.endsWith('.localhost') || url.hostname === '[::1]' || url.hostname.startsWith('127.')) {
    throw new Error('Localhost on the phone is not your computer. Use the computer Wi-Fi IP address.');
  }
  const parts = url.hostname.split('.').map(Number);
  const privateIp = parts.length === 4 && parts.every((part) => Number.isInteger(part) && part >= 0 && part <= 255)
    && (parts[0] === 10 || (parts[0] === 192 && parts[1] === 168) || (parts[0] === 172 && parts[1]! >= 16 && parts[1]! <= 31));
  if (url.protocol === 'http:' && !privateIp) {
    throw new Error('Use HTTPS for a hosted server. HTTP is allowed only for the local Wi-Fi demonstration.');
  }
  url.pathname = url.pathname.replace(/\/+$/, '') || '/api';
  return url.toString().replace(/\/$/, '');
}
