<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function show(Request $request)
    {
        $intended = (string) $request->session()->get('url.intended');
        $marketIntended = str_contains($intended, '/bidang-pasar');
        $industryIntended = str_contains($intended, '/bidang-industri');
        $metrologyIntended = str_contains($intended, '/bidang-metrologi');
        $secretariatIntended = str_contains($intended, '/sekretariat');
        $service = $request->query('service') === 'secretariat' || $secretariatIntended ? 'secretariat' : ($request->query('service') === 'metrology' || $metrologyIntended ? 'metrology' : ($request->query('service') === 'industry' || $industryIntended ? 'industry' : ($request->query('service') === 'market' || $marketIntended ? 'market' : 'trade')));
        if ($service === 'market') {
            $request->session()->put('url.intended', route('market-data.index'));
        } elseif ($service === 'industry') {
            $request->session()->put('url.intended', route('industry.dashboard'));
        } elseif ($service === 'metrology') {
            $request->session()->put('url.intended', route('metrology.dashboard'));
        } elseif ($service === 'secretariat') {
            $request->session()->put('url.intended', route('secretariat.dashboard'));
        }

        return view('auth.login', compact('service'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['username' => 'required|string', 'password' => 'required|string']);
        if (! Auth::attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'Username atau kata sandi tidak sesuai.'])->onlyInput('username');
        }
        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away('https://sidagang.appcatalog.id/');
    }
}
