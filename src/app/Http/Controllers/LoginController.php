<?php

namespace App\Http\Controllers;

class LoginController extends Controller
{
    /**
     * 一般userのログイン画面
     */
    public function user()
    {
        return view('auth.login');
    }

    /**
     * 管理者のログイン画面
     */
    public function admin()
    {
        return view('admin.login');
    }
}
