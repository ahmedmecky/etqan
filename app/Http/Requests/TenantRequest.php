<?php

namespace App\Http\Requests;

use App\Support\Egypt;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * التحقق من بيانات العميل (الشركة / المخزن)
 */
class TenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperAdmin();
    }

    protected function prepareForValidation(): void
    {
        // مناطق التغطية: بدون مسافات زائدة وبدون تكرار وبدون الفارغ
        $zones = collect((array) $this->input('coverage_zones', []))
            ->map(fn ($z) => trim(preg_replace('/\s+/u', ' ', (string) $z)))
            ->filter(fn ($z) => $z !== '')
            ->unique()
            ->values()
            ->all();

        // GLN: الأرقام فقط (يُسمح بكتابته بمسافات أو شرطات)
        $gln = preg_replace('/\D+/', '', (string) $this->input('gln'));

        $this->merge([
            'code'           => strtoupper(trim((string) $this->input('code'))),
            'coverage_zones' => $zones,
            'gln'            => $gln === '' ? null : $gln,
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('tenant')?->id;

        return [
            'code'             => ['required', 'string', 'min:2', 'max:10', 'regex:/^[A-Z0-9]+$/', Rule::unique('tenants', 'code')->ignore($id)],
            'name_ar'          => ['required', 'string', 'max:255'],
            'name_en'          => ['required', 'string', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:30'],
            'address'          => ['nullable', 'string', 'max:255'],
            'city'             => ['required', Rule::in(Egypt::GOVERNORATES)],
            'coverage_zones'   => ['nullable', 'array', 'max:60'],
            'coverage_zones.*' => ['string', 'max:60'],
            'gln'              => ['nullable', 'digits:13', Rule::unique('tenants', 'gln')->ignore($id),
                function (string $attr, mixed $value, Closure $fail) {
                    if ($value !== null && preg_match('/^\d{13}$/', $value) && ! Egypt::validGln($value)) {
                        $fail('رقم GLN غير صحيح: رقم التحقق الأخير لا يطابق. راجع الرقم من شهادة GS1.');
                    }
                }],
            'logo'             => ['nullable', 'image', 'mimes:png', 'max:1024'],
            'remove_logo'      => ['nullable', 'boolean'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'          => 'اكتب كود العميل.',
            'code.min'               => 'كود العميل حرفان على الأقل.',
            'code.max'               => 'كود العميل لا يزيد عن ١٠ أحرف.',
            'code.regex'             => 'كود العميل حروف إنجليزية وأرقام فقط، بدون مسافات (مثال: TGT).',
            'code.unique'            => 'هذا الكود مستخدم لعميل آخر.',
            'name_ar.required'       => 'اكتب اسم العميل بالعربية.',
            'name_en.required'       => 'اكتب اسم العميل بالإنجليزية.',
            'city.required'          => 'اختر المدينة (المحافظة).',
            'city.in'                => 'اختر المدينة من القائمة.',
            'coverage_zones.max'     => 'عدد مناطق التغطية كبير جداً (٦٠ منطقة بحد أقصى).',
            'coverage_zones.*.max'   => 'اسم منطقة التغطية طويل جداً.',
            'gln.digits'             => 'رقم GLN يتكون من ١٣ رقماً.',
            'gln.unique'             => 'رقم GLN مسجل لعميل آخر.',
            'logo.image'             => 'الشعار يجب أن يكون صورة.',
            'logo.mimes'             => 'الشعار يجب أن يكون بصيغة PNG.',
            'logo.max'               => 'حجم الشعار يجب ألا يزيد عن ١ ميجابايت.',
        ];
    }
}