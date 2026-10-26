/* إتقان · Service Worker
 * - استقبال إشعارات الموبايل (Web Push) وعرضها بصوت الجهاز حتى والمتصفح مغلق
 * - الضغط على الإشعار يفتح السجل المرتبط
 * - صفحة «لا يوجد اتصال» عند انقطاع الإنترنت
 */
const VERSION = 'etqan-sw-1';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));

self.addEventListener('push', e => {
  let d = {};
  try { d = e.data ? e.data.json() : {}; } catch (err) { d = { title: 'إتقان', body: e.data ? e.data.text() : '' }; }
  const title = d.title || 'إتقان';
  const opts = {
    body: d.body || '',
    icon: '/icons/etqan-192.png',
    badge: '/icons/badge-96.png',
    tag: d.tag || ('etqan-' + Date.now()),
    renotify: true,                       // صوت واهتزاز حتى لو استُبدل إشعار سابق بنفس الوسم
    requireInteraction: !!d.urgent,       // العاجل يبقى ظاهراً حتى يضغطه المستخدم
    vibrate: d.urgent ? [300, 120, 300, 120, 300] : [200, 100, 200],
    dir: 'rtl',
    lang: 'ar',
    timestamp: Date.now(),
    data: { url: d.url || '/notifications', id: d.id || null },
  };
  e.waitUntil(Promise.all([
    self.registration.showNotification(title, opts),
    // الصفحة مفتوحة: تحديث الجرس وتشغيل صوت داخل الصفحة
    self.clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(list => list.forEach(c => c.postMessage({ type: 'etqan-push', id: d.id, urgent: !!d.urgent }))),
  ]));
});

self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = new URL((e.notification.data && e.notification.data.url) || '/notifications', self.location.origin).href;
  e.waitUntil((async () => {
    const list = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const c of list) {
      if (new URL(c.url).origin === self.location.origin && 'focus' in c) {
        await c.focus();
        if ('navigate' in c) return c.navigate(url);
        return;
      }
    }
    return self.clients.openWindow(url);
  })());
});

// انتهى الاشتراك أو تغيّر من المتصفح: يُعاد التفعيل عند فتح النظام
self.addEventListener('pushsubscriptionchange', () => {});

const OFFLINE = `<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>لا يوجد اتصال · إتقان</title>
<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:Cairo,Segoe UI,Tahoma,sans-serif;background:#f4f6f5;color:#1c2826;text-align:center;padding:24px}
.c{background:#fff;border:1px solid #dde3e1;border-radius:14px;padding:28px 24px;max-width:360px}b{display:block;font-size:20px;color:#0f5e5a;margin-bottom:8px}
button{margin-top:16px;background:#0f5e5a;color:#fff;border:0;border-radius:8px;padding:10px 22px;font:inherit;font-weight:700}</style></head>
<body><div class="c"><b>لا يوجد اتصال بالإنترنت</b>إتقان يحتاج اتصالاً لعرض البيانات وحفظها. تأكد من الشبكة ثم أعد المحاولة.<br><button onclick="location.reload()">إعادة المحاولة</button></div></body></html>`;

self.addEventListener('fetch', e => {
  if (e.request.mode !== 'navigate') return;     // البيانات دائماً من الخادم مباشرة (لا تخزين)
  e.respondWith(fetch(e.request).catch(() => new Response(OFFLINE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
});