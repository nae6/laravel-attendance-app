<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Services\AdminStaffService;

class AdminStaffController extends Controller
{
    public function __construct(
        private AdminStaffService $adminStaffService
    ) {
    }

    /**
     * スタッフ一覧画面の表示
     *
     * @return View
     */
    public function index(): View {
        $users = $this->adminStaffService->getStaffList();

        return view('admin.staff_list', compact('users'));
    }
}
