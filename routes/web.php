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

    // شاشات النماذج: مدير العميل ومستخدمو المخزن
    Route::middleware('level:2,3')
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

    // متابعة قرارات الاجتماعات (SH-034-F03)
    Route::middleware('level:2,3')->group(function () {
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

        // التحكم في الوثائق: السجل · قائمة الانتظار · الإصدارات والاعتماد (SH-014)
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/queue', [DocumentController::class, 'queue'])->name('documents.queue');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::post('/documents/seed', [DocumentController::class, 'seed'])->name('documents.seed');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->whereNumber('document')->name('documents.show');
        Route::put('/documents/{document}', [DocumentController::class, 'update'])->whereNumber('document')->name('documents.update');
        Route::post('/documents/{document}/toggle', [DocumentController::class, 'toggle'])->whereNumber('document')->name('documents.toggle');
        Route::post('/documents/{document}/versions', [DocumentController::class, 'startVersion'])->whereNumber('document')->name('documents.version.start');
        Route::prefix('/documents/{document}/versions/{version}')->whereNumber(['document', 'version'])->group(function () {
            Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');
            Route::post('/submit', [DocumentController::class, 'submit'])->name('documents.submit');
            Route::post('/approve', [DocumentController::class, 'approve'])->name('documents.approve');
            Route::post('/return', [DocumentController::class, 'returnBack'])->name('documents.return');
            Route::get('/download', [DocumentController::class, 'download'])->name('documents.download');
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
    });
});