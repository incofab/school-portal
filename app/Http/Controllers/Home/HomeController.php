<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    //         $this->middleware('auth');
  }

  /**
   * Show the application dashboard.
   *
   * @return \Illuminate\Contracts\Support\Renderable
   */
  public function index()
  {
    $scheme = request()->getScheme(); // 'http' or 'https'
    $currentDomain = request()->getHost();

    $isForEdumanager =
      $currentDomain == 'localhost' ||
      str_contains($currentDomain, 'edumanager.ng');

    if ($isForEdumanager) {
      return view('home.index', []);
    }

    //= Taking care of "localho.st:8000"
    $currentDomain = str_contains($currentDomain, 'localho.st')
      ? 'localho.st:8000'
      : $currentDomain;

    //= Return
    return redirect()->away("$scheme://$currentDomain/login");
  }

  public function monnifyCheckout(Request $request)
  {
    return view('home.monnify-checkout', [
      'amount' => $request->input('amount'),
      'reference' => $request->input('reference')
    ]);
  }

  public function privacyPolicy()
  {
    return redirect()->route('home');
    return view('home.privacy-policy', []);
  }

  function error()
  {
    return view('home.error', [
      'message' => session('message', 'An error occurred.')
    ]);
  }
}
