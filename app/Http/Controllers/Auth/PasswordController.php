<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(
            [
                'current_password' => ['required', 'current_password'],
                'password'         => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/', 'confirmed', 'different:current_password'],
            ],
            [
                'current_password.required'         => 'اكتب كلمة المرور الحالية.',
                'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.',
                'password.required'                 => 'اكتب كلمة المرور الجديدة.',
                'password.min'                      => 'كلمة المرور الجديدة يجب ألا تقل عن ٨ أحرف.',
                'password.regex'                    => 'كلمة المرور الجديدة يجب أن تحتوي على حروف إنجليزية وأرقام.',
                'password.confirmed'                => 'تأكيد كلمة المرور غير مطابق.',
                'password.different'                => 'كلمة المرور الجديدة يجب أن تختلف عن الحالية.',
            ]
        );

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('home')->with('status', 'تم تغيير كلمة المرور بنجاح.');
    }
}