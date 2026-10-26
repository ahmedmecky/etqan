<?php

namespace App\Support;

/**
 * Web Push بدون مكتبات خارجية (PHP openssl + curl فقط)
 *   - التشفير: RFC 8291 (aes128gcm) · RFC 8188
 *   - التوقيع: VAPID (RFC 8292) بمفتاح ES256 (P-256)
 * المفاتيح بصيغة base64url القياسية (نفس صيغة مكتبات web-push المعروفة):
 *   VAPID_PUBLIC_KEY  = 65 بايت (04 || X || Y)
 *   VAPID_PRIVATE_KEY = 32 بايت (D)
 */
class WebPush
{
    /** OID: id-ecPublicKey · prime256v1 */
    private const SPKI_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';
    private const RECORD_SIZE = 4096;

    /* ===================== مفاتيح VAPID ===================== */

    /** إنشاء زوج مفاتيح جديد: ['public' => ..., 'private' => ...] */
    public static function createKeys(): array
    {
        $ec = openssl_pkey_get_details(self::newKey())['ec'];

        return [
            'public'  => self::b64e("\x04" . self::pad32($ec['x']) . self::pad32($ec['y'])),
            'private' => self::b64e(self::pad32($ec['d'])),
        ];
    }

    /** مفتاح P-256 جديد (على ويندوز قد يحتاج OpenSSL ملف إعداد: نكتب ملفاً بسيطاً ونعيد المحاولة) */
    private static function newKey()
    {
        $opts = ['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC];
        $key = @openssl_pkey_new($opts);
        if (! $key) {
            $cnf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'etqan-openssl.cnf';
            if (! is_file($cnf)) {
                @file_put_contents($cnf, "[req]\ndistinguished_name = dn\n[dn]\n");
            }
            $key = @openssl_pkey_new($opts + ['config' => $cnf]);
        }
        while (openssl_error_string() !== false) {
            // تفريغ رسائل OpenSSL القديمة
        }
        if (! $key) {
            throw new \RuntimeException('OpenSSL: تعذر إنشاء مفتاح P-256 (تأكد من تفعيل امتداد openssl في PHP).');
        }

        return $key;
    }

    public static function configured(): bool
    {
        return (bool) (config('webpush.public_key') && config('webpush.private_key'));
    }

    /* ===================== الإرسال ===================== */

    /**
     * إرسال رسائل لعدة اشتراكات معاً (curl_multi)
     * $jobs: [['endpoint' => ..., 'p256dh' => ..., 'auth' => ..., 'payload' => string, 'urgent' => bool, 'topic' => ?string], ...]
     * يرجع رمز HTTP لكل رسالة بنفس الترتيب (0 = فشل الاتصال)
     */
    public static function sendMany(array $jobs, int $ttl = 86400): array
    {
        if (! $jobs || ! self::configured()) {
            return array_fill(0, count($jobs), 0);
        }

        $mh = curl_multi_init();
        $handles = [];
        $codes = [];
        foreach (array_values($jobs) as $i => $job) {
            $codes[$i] = 0;
            try {
                $req = self::request($job['endpoint'], $job['p256dh'], $job['auth'], $job['payload'], $ttl, ! empty($job['urgent']), $job['topic'] ?? null);
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
            $ch = curl_init($job['endpoint']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $req['body'],
                CURLOPT_HTTPHEADER     => $req['headers'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT        => 10,
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $i => $ch) {
            $codes[$i] = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $codes;
    }

    /** بناء طلب HTTP مشفر وموقع لاشتراك واحد: ['headers' => [...], 'body' => binary] */
    public static function request(string $endpoint, string $p256dh, string $auth, string $payload, int $ttl = 86400, bool $urgent = false, ?string $topic = null): array
    {
        $body = self::encrypt($payload, self::b64d($p256dh), self::b64d($auth));

        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aes128gcm',
            'Content-Length: ' . strlen($body),
            'TTL: ' . $ttl,
            'Urgency: ' . ($urgent ? 'high' : 'normal'),
            'Authorization: ' . self::vapidHeader($endpoint),
        ];
        if ($topic) {
            // Topic: يستبدل رسالة سابقة لم تُسلم بنفس الموضوع (حتى ٣٢ حرفاً base64url)
            $headers[] = 'Topic: ' . substr(preg_replace('/[^A-Za-z0-9_-]/', '', $topic), 0, 32);
        }

        return ['headers' => $headers, 'body' => $body];
    }

    /* ===================== التشفير (RFC 8291) ===================== */

    /**
     * تشفير الرسالة لمتصفح المستلم
     * $uaPublic: مفتاح المتصفح (65 بايت) · $authSecret: 16 بايت
     * $salt و $asKey للاختبار فقط (تُنشأ عشوائياً عادة)
     */
    public static function encrypt(string $payload, string $uaPublic, string $authSecret, ?string $salt = null, $asKey = null): string
    {
        if (strlen($uaPublic) !== 65 || $uaPublic[0] !== "\x04") {
            throw new \InvalidArgumentException('مفتاح الاشتراك (p256dh) غير صحيح.');
        }
        if (strlen($authSecret) < 16) {
            throw new \InvalidArgumentException('مفتاح الاشتراك (auth) غير صحيح.');
        }

        $salt ??= random_bytes(16);
        $asKey ??= self::newKey();
        $ec = openssl_pkey_get_details($asKey)['ec'];
        $asPublic = "\x04" . self::pad32($ec['x']) . self::pad32($ec['y']);

        $peer = openssl_pkey_get_public(self::spkiPem($uaPublic));
        if (! $peer) {
            throw new \InvalidArgumentException('مفتاح الاشتراك (p256dh) ليس نقطة صحيحة على المنحنى.');
        }
        $shared = openssl_pkey_derive($peer, $asKey, 32);
        if ($shared === false || strlen($shared) !== 32) {
            throw new \RuntimeException('OpenSSL: فشل حساب المفتاح المشترك (ECDH).');
        }

        // IKM = HKDF(auth, ecdh, "WebPush: info\0" || ua_public || as_public, 32)
        $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\x00" . $uaPublic . $asPublic, $authSecret);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

        // سجل واحد: الرسالة + 0x02 (فاصل السجل الأخير)
        $tag = '';
        $cipher = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($cipher === false) {
            throw new \RuntimeException('OpenSSL: فشل التشفير (AES-128-GCM).');
        }

        // الرأس: salt(16) || rs(4) || idlen(1) || keyid(as_public)
        return $salt . pack('N', self::RECORD_SIZE) . chr(65) . $asPublic . $cipher . $tag;
    }

    /* ===================== VAPID (RFC 8292) ===================== */

    public static function vapidHeader(string $endpoint): string
    {
        $p = parse_url($endpoint);
        $aud = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');

        $jwtHeader = self::b64e(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64e(json_encode([
            'aud' => $aud,
            'exp' => time() + 12 * 3600,
            'sub' => config('webpush.subject'),
        ], JSON_UNESCAPED_SLASHES));
        $input = $jwtHeader . '.' . $claims;

        $der = '';
        if (! openssl_sign($input, $der, self::privatePem(), OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('OpenSSL: فشل توقيع VAPID. راجع VAPID_PRIVATE_KEY.');
        }

        return 'vapid t=' . $input . '.' . self::b64e(self::derToRaw($der)) . ', k=' . config('webpush.public_key');
    }

    /* ===================== أدوات ===================== */

    public static function b64e(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    public static function b64d(string $s): string
    {
        $s = strtr(trim($s), '-_', '+/');

        return (string) base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4), true);
    }

    private static function pad32(string $b): string
    {
        return str_pad($b, 32, "\x00", STR_PAD_LEFT);
    }

    /** مفتاح عام خام (65 بايت) ← PEM (SubjectPublicKeyInfo) */
    private static function spkiPem(string $raw): string
    {
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode(hex2bin(self::SPKI_PREFIX) . $raw), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /** مفتاح VAPID الخاص (D + المفتاح العام) ← PEM (SEC1 ECPrivateKey) */
    private static function privatePem(): string
    {
        $d = self::b64d((string) config('webpush.private_key'));
        $pub = self::b64d((string) config('webpush.public_key'));
        if (strlen($d) !== 32 || strlen($pub) !== 65) {
            throw new \RuntimeException('مفاتيح VAPID غير صحيحة. شغّل: php artisan etqan:push-keys');
        }
        $der = hex2bin('30770201010420') . $d . hex2bin('a00a06082a8648ce3d030107a144034200') . $pub;

        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    /** توقيع DER (SEQUENCE { r, s }) ← r || s (64 بايت) كما يتطلب JWT */
    private static function derToRaw(string $der): string
    {
        $pos = 2;
        if (ord($der[1]) & 0x80) {
            $pos += ord($der[1]) & 0x7f;
        }
        $out = '';
        for ($k = 0; $k < 2; $k++) {
            $pos++;                       // 0x02 INTEGER
            $len = ord($der[$pos++]);
            $int = substr($der, $pos, $len);
            $pos += $len;
            $out .= str_pad(ltrim($int, "\x00"), 32, "\x00", STR_PAD_LEFT);
        }

        return $out;
    }
}