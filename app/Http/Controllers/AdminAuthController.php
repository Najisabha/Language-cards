<?php

namespace App\Http\Controllers;

use App\Support\AdminGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (AdminGate::isAuthenticated()) {
            return redirect()->route('languages.index');
        }

        return view('auth.login', [
            'adminConfigured' => AdminGate::isConfigured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! AdminGate::isConfigured()) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'لم يُضبط حساب المدير بعد. أضف ADMIN_USERNAME و ADMIN_PASSWORD في ملف .env']);
        }

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! AdminGate::attempt($credentials['username'], $credentials['password'])) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'اسم المستخدم أو كلمة المرور غير صحيحة.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('languages.index'))
            ->with('status', 'مرحبًا! أنت مسجّل كمدير.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        AdminGate::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('languages.index')
            ->with('status', 'تم تسجيل الخروج.');
    }
}
