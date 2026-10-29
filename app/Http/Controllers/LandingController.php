<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing.index');
    }

    public function pricelist()
    {
        $packages = Package::where('slug','not like','uas-%')->where('is_active', true)->get();
        return view('landing.pricelist', compact('packages'));
    }

    public function testimoni()
    {
        return view('landing.testimoni');
    }

    public function kalkulator()
    {
        return view('landing.kalkulator');
    }

    public function simulasi()
    {
        return view('landing.simulasi');
    }

    public function checkout()
    {
        return view('landing.checkout');
    }

    public function akses()
    {
        return view('landing.akses');
    }

    public function paketSoal()
    {
        return view('landing.paket-soal');
    }
}
