<?php

/*
|--------------------------------------------------------------------------
| إشعارات الموبايل (Web Push)
|--------------------------------------------------------------------------
| المفاتيح تُنشأ مرة واحدة لكل خادم:  php artisan etqan:push-keys
| (تُكتب في ملف .env ولا تُرفع على GitHub)
| تغيير المفاتيح يُلغي اشتراك كل الأجهزة، وعلى كل مستخدم تفعيل الإشعارات من جديد.
*/

return [
    'public_key'  => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),
    'subject'     => env('VAPID_SUBJECT', 'mailto:support@etqanpharma.tech'),
];