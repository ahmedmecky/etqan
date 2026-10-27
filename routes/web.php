<?php

use App\Http\Controllers\Admin\TenantAdminController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Client\MasterDataController;
use App\Http\Controllers\Client\SettingsController;
use App\Http\Controllers\Client\UserController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\DeviationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FormRecordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\OrderCheckController;
use App\Http\Controllers\Portal\ComplaintPortalController;
use App\Http\Controllers\Portal\FieldInspectionPortalController;
use App\Http\Controllers\FieldInspectionController;
use App\Http\Controllers\QmsMapController;
use App\Http\Controllers\FlowChartController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\Client\PermissionController;
use App\Http\Controllers\Client\NotifyRuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\ModuleGate;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\TrainingController;
use App\Support\FormRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| إتقان · المسارات
|--------------------------------------------------------------------------
*/

// الزائر (غير مسجل الدخول)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

// رابط شكاوى الصيدليات (بدون تسجيل دخول)
Route::prefix('c/{token}')
    ->where(['token' => '[A-Za-z0-9]{16,40}'])
    ->name('portal.')
    ->group(function () {
        Route::get('/', [ComplaintPortalController::class, 'show'])->name('complaint');
        Route::post('/', [ComplaintPortalController::class, 'store'])->middleware('throttle:5,1')->name('store');
        Route::get('/pharmacy', [ComplaintPortalController::class, 'pharmacy'])->middleware('throttle:20,1')->name('pharmacy');
        Route::get('/done', [ComplaintPortalController::class, 'thanks'])->name('thanks');
    });

// رابط التفتيش الميداني الشخصي للمفوض (بدون تسجيل دخول) · SH-012-F04
Route::prefix('i/{token}')
    ->where(['token' => '[A-Za-z0-9]{32}'])
    ->name('field.')
    ->group(function () {
        Route::get('/', [FieldInspectionPortalController::class, 'show'])->name('form');
        Route::post('/', [FieldInspectionPortalController::class, 'store'])->middleware('throttle:10,1')->name('store');
        Route::get('/products', [FieldInspectionPortalController::class, 'products'])->middleware('throttle:90,1')->name('products');
        Route::get('/pharmacies', [FieldInspectionPortalController::class, 'pharmacies'])->middleware('throttle:60,1')->name('pharmacies');
    });

// المستخدم المسجل
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', fn () => redirect()->route('home'));
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // مدير النظام فقط
    Route::middleware('level:1')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
        Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
        Route::patch('/tenants/{tenant}/toggle', [TenantController::class, 'toggle'])->name('tenants.toggle');

        Route::post('/tenants/{tenant}/admins', [TenantAdminController::class, 'store'])->name('tenants.admins.store');
        Route::put('/tenants/{tenant}/admins/{user}', [TenantAdminController::class, 'update'])->name('tenants.admins.update');
        Route::patch('/tenants/{tenant}/admins/{user}/toggle', [TenantAdminController::class, 'toggle'])->name('tenants.admins.toggle');
    });

    // مدير العميل فقط
    Route::middleware('level:2')->prefix('client')->name('client.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

        // الصلاحيات: الأدوار × الشاشات (الافتراضي من RACI)
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::put('/permissions', [PermissionController::class, 'update'])->name('permissions.update');
        Route::post('/permissions/reset', [PermissionController::class, 'reset'])->name('permissions.reset');

        // الإشعارات: مستلمو كل حدث · إشعار تجريبي لمستخدم
        Route::get('/notifications', [NotifyRuleController::class, 'index'])->name('notify.index');
        Route::put('/notifications', [NotifyRuleController::class, 'update'])->name('notify.update');
        Route::post('/notifications/test/{user}', [NotifyRuleController::class, 'test'])->whereNumber('user')->middleware('throttle:10,1')->name('notify.test');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::patch('/settings/portal', [SettingsController::class, 'togglePortal'])->name('settings.portal.toggle');
        Route::post('/settings/portal/regenerate', [SettingsController::class, 'regeneratePortal'])->name('settings.portal.regenerate');

        // الأصناف والصيدليات (رفع Excel)
        Route::prefix('data/{type}')->where(['type' => 'products|pharmacies'])->name('data.')->group(function () {
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
            Route::post('/', [MasterDataController::class, 'store'])->name('store');
            Route::post('/upload', [MasterDataController::class, 'upload'])->name('upload');
            Route::get('/template', [MasterDataController::class, 'template'])->name('template');
            Route::get('/export', [MasterDataController::class, 'export'])->name('export');
            Route::delete('/', [MasterDataController::class, 'clear'])->name('clear');
            Route::put('/{id}', [MasterDataController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [MasterDataController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
    });

    // الإشعارات: الجرس · الصندوق · تفعيل الإشعار على الجهاز (كل مستخدمي المخزن)
    Route::middleware('level:2,3')->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/feed', [NotificationController::class, 'feed'])->name('feed');
        Route::get('/{id}/open', [NotificationController::class, 'open'])->whereNumber('id')->name('open');
        Route::post('/read-all', [NotificationController::class, 'readAll'])->name('readAll');
        Route::post('/subscribe', [NotificationController::class, 'subscribe'])->middleware('throttle:20,1')->name('subscribe');
        Route::post('/unsubscribe', [NotificationController::class, 'unsubscribe'])->name('unsubscribe');
        Route::post('/test', [NotificationController::class, 'test'])->middleware('throttle:6,1')->name('test');
    });

    // شاشات النماذج: مدير العميل ومستخدمو المخزن
    Route::middleware(['level:2,3', ModuleGate::class])
        ->prefix('forms/{form}')
        ->where(['form' => FormRegistry::pattern()])
        ->name('forms.')
        ->group(function () {
            Route::get('/', [FormRecordController::class, 'page'])->name('page');
            Route::get('/records', [FormRecordController::class, 'index'])->name('records.index');
            Route::post('/records', [FormRecordController::class, 'store'])->name('records.store');
            Route::get('/records/{record}', [FormRecordController::class, 'show'])->whereNumber('record')->name('records.show');
            Route::put('/records/{record}', [FormRecordController::class, 'update'])->whereNumber('record')->name('records.update');
            Route::post('/records/{record}/reopen', [FormRecordController::class, 'reopen'])->whereNumber('record')->name('records.reopen');
            Route::delete('/records/{record}', [FormRecordController::class, 'destroy'])->whereNumber('record')->name('records.destroy');
        });

    // متابعة قرارات الاجتماعات (SH-034-F03) · كل شاشات المخزن تمر ببوابة الصلاحيات
    Route::middleware(['level:2,3', ModuleGate::class])->group(function () {
        Route::get('/decisions', [DecisionController::class, 'index'])->name('decisions.index');
        Route::put('/decisions/{record}/{key}', [DecisionController::class, 'update'])
            ->whereNumber('record')
            ->where('key', '[A-Za-z0-9_-]+')
            ->name('decisions.update');

        // سجل الانحرافات والإجراءات التصحيحية (SH-008-F04)
        Route::get('/deviations', [DeviationController::class, 'index'])->name('deviations.index');
        Route::get('/deviations/links', [DeviationController::class, 'links'])->name('deviations.links');

        // سجل الشكاوى وتحليل الاتجاهات (SH-007-F03)
        Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');

        // التفتيش الذاتي: السجل والتغطية · بنك بنود الفحص (SH-012)
        Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections.index');
        Route::get('/inspections/bank', [InspectionController::class, 'processes'])->name('inspections.bank');
        Route::get('/inspections/bank/{code}', [InspectionController::class, 'bank'])->where('code', 'SH-\d{3}')->name('inspections.bank.items');
        Route::get('/inspections/quick', [InspectionController::class, 'quickAreas'])->name('inspections.quick');
        Route::get('/inspections/quick/{area}', [InspectionController::class, 'quickItems'])->where('area', '[a-z]+')->name('inspections.quick.items');

        // فحص دقة التحضير: تقرير الزونات والمحضرين · قائمة الأصناف بالموقع (SH-004-F04)
        Route::get('/order-checks', [OrderCheckController::class, 'index'])->name('orderchecks.index');
        Route::get('/order-checks/products', [OrderCheckController::class, 'products'])->name('orderchecks.products');

        // تقييم الأداء الشهري: السجل · مجموعات المؤشرات
        Route::get('/kpi', [KpiController::class, 'index'])->name('kpi.index');
        Route::get('/kpi/sets', [KpiController::class, 'sets'])->name('kpi.sets');

        // التحكم في الوثائق: السجل · قائمة الانتظار · الإصدارات والاعتماد · مكتبة إتقان والطباعة (SH-014)
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/queue', [DocumentController::class, 'queue'])->name('documents.queue');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::post('/documents/seed', [DocumentController::class, 'seed'])->name('documents.seed');
        Route::post('/documents/library-all', [DocumentController::class, 'libraryAll'])->name('documents.library.all');
        Route::post('/documents/submit-all', [DocumentController::class, 'submitAll'])->name('documents.submit.all');
        Route::post('/documents/approve-bulk', [DocumentController::class, 'approveBulk'])->name('documents.approve.bulk');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->whereNumber('document')->name('documents.show');
        Route::put('/documents/{document}', [DocumentController::class, 'update'])->whereNumber('document')->name('documents.update');
        Route::post('/documents/{document}/toggle', [DocumentController::class, 'toggle'])->whereNumber('document')->name('documents.toggle');
        Route::post('/documents/{document}/versions', [DocumentController::class, 'startVersion'])->whereNumber('document')->name('documents.version.start');
        Route::post('/documents/{document}/library', [DocumentController::class, 'startLibrary'])->whereNumber('document')->name('documents.library');
        Route::get('/documents/{document}/print', [DocumentController::class, 'print'])->whereNumber('document')->name('documents.print');
        Route::prefix('/documents/{document}/versions/{version}')->whereNumber(['document', 'version'])->group(function () {
            Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');
            Route::post('/submit', [DocumentController::class, 'submit'])->name('documents.submit');
            Route::post('/approve', [DocumentController::class, 'approve'])->name('documents.approve');
            Route::post('/return', [DocumentController::class, 'returnBack'])->name('documents.return');
            Route::get('/download', [DocumentController::class, 'download'])->name('documents.download');
            Route::get('/fills', [DocumentController::class, 'fills'])->name('documents.fills');
            Route::put('/fills', [DocumentController::class, 'saveFills'])->name('documents.fills.save');
            Route::delete('/', [DocumentController::class, 'destroyVersion'])->name('documents.version.destroy');
        });

        // الحرارة والرطوبة: جولة القراءات · نقاط المراقبة · سجل نصف الشهر · التقرير الشهري (SH-030)
        Route::get('/temperature', [TemperatureController::class, 'round'])->name('temperature.round');
        Route::post('/temperature', [TemperatureController::class, 'saveRound'])->name('temperature.save');
        Route::get('/temperature/points', [TemperatureController::class, 'pointsPage'])->name('temperature.points');
        Route::post('/temperature/points', [TemperatureController::class, 'storePoint'])->name('temperature.points.store');
        Route::post('/temperature/points/seed', [TemperatureController::class, 'seedPoints'])->name('temperature.points.seed');
        Route::get('/temperature/points.json', [TemperatureController::class, 'pointsJson'])->name('temperature.points.json');
        Route::put('/temperature/points/{point}', [TemperatureController::class, 'updatePoint'])->whereNumber('point')->name('temperature.points.update');
        Route::post('/temperature/points/{point}/toggle', [TemperatureController::class, 'togglePoint'])->whereNumber('point')->name('temperature.points.toggle');
        Route::get('/temperature/readings/{reading}', [TemperatureController::class, 'readingJson'])->whereNumber('reading')->name('temperature.reading');
        Route::get('/temperature/sheet', [TemperatureController::class, 'sheet'])->name('temperature.sheet');
        Route::get('/temperature/report', [TemperatureController::class, 'report'])->name('temperature.report');
        Route::post('/temperature/checks', [TemperatureController::class, 'storeCheck'])->name('temperature.checks');

        // التدريب: تقرير الدورات والمحاضرين · سجل تدريب الموظف (SH-015)
        Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
        Route::get('/training/employee', [TrainingController::class, 'employee'])->name('training.employee');

        // الهيكل الوظيفي والأوصاف الوظيفية (SH-021)
        Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/{code}', [JobController::class, 'edit'])->where('code', 'JD-[A-Z]{2}-\d{2}')->name('jobs.edit');
        Route::put('/jobs/{code}', [JobController::class, 'update'])->where('code', 'JD-[A-Z]{2}-\d{2}')->name('jobs.update');
        Route::get('/jobs/{code}/print', [JobController::class, 'printJob'])->where('code', 'JD-[A-Z]{2}-\d{2}')->name('jobs.print');
        Route::get('/jobs/user/{user}', [JobController::class, 'printUser'])->whereNumber('user')->name('jobs.user');
        Route::post('/jobs/user/{user}/ack', [JobController::class, 'acknowledge'])->whereNumber('user')->name('jobs.ack');
        Route::get('/my-job', [JobController::class, 'mine'])->name('jobs.mine');

        // خريطة نظام الجودة ومصفوفة المسؤوليات
        Route::get('/qms-map', QmsMapController::class)->name('qms.map');
        Route::get('/qms-flows', FlowChartController::class)->name('qms.flows');

        // مهام اليوم: التنفيذ · الإعداد (مدير المخزن ومدير الجودة)
        Route::get('/daily', [DailyTaskController::class, 'index'])->name('daily.index');
        Route::post('/daily/tasks', [DailyTaskController::class, 'store'])->name('daily.tasks.store');
        Route::post('/daily/tasks/seed', [DailyTaskController::class, 'seed'])->name('daily.tasks.seed');
        Route::put('/daily/tasks/{task}', [DailyTaskController::class, 'update'])->whereNumber('task')->name('daily.tasks.update');
        Route::post('/daily/tasks/{task}/toggle', [DailyTaskController::class, 'toggle'])->whereNumber('task')->name('daily.tasks.toggle');
        Route::post('/daily/{task}/done', [DailyTaskController::class, 'done'])->whereNumber('task')->name('daily.done');
        Route::delete('/daily/{task}/done', [DailyTaskController::class, 'undo'])->whereNumber('task')->name('daily.undo');

        // التفتيش الميداني بالتفويض: السجل · المراجعة · المفوضون (SH-012-F04)
        Route::get('/inspections/field', [FieldInspectionController::class, 'index'])->name('field.index');
        Route::get('/inspections/field/{record}', [FieldInspectionController::class, 'show'])->whereNumber('record')->name('field.show');
        Route::post('/inspections/field/{record}/review', [FieldInspectionController::class, 'review'])->whereNumber('record')->name('field.review');
        Route::post('/inspections/delegates', [FieldInspectionController::class, 'storeDelegate'])->name('field.delegates.store');
        Route::post('/inspections/delegates/{delegate}/toggle', [FieldInspectionController::class, 'toggleDelegate'])->whereNumber('delegate')->name('field.delegates.toggle');
        Route::post('/inspections/delegates/{delegate}/renew', [FieldInspectionController::class, 'renewDelegate'])->whereNumber('delegate')->name('field.delegates.renew');
    });
});