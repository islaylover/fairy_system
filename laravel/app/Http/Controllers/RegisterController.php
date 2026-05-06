<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PreRegisterService;

use Log;

class RegisterController extends Controller
{
    public function __construct(private PreRegisterService $preRegisterService) {}

    public function confirm(Request $request)
    {
        Log::info("app/Http/Controllers/RegisterController.php #confirm called");
        $token = $request->query('token');
        $pre = $this->preRegisterService->getByToken($token);

        if (! $pre) {
            return redirect('/register?error=invalid_token');
        }

        return redirect("/register?token={$token}&email={$pre->getPreRegisterEmail()->getValue()}");
    }
}