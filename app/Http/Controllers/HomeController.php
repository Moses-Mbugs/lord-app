<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $modules = [
            [
                'key' => 'finance',
                'name' => 'Finance Dashboard',
                'description' => 'Balances, top movers, branch movers, RM performance and customer profitability.',
                'icon' => '📊',
                'route' => 'finance.dashboard',
                'visible' => true,
            ],
            [
                'key' => 'loans',
                'name' => 'Loan Book Generator',
                'description' => 'Stage PMS and Loan Details reports, reconcile against Portfolio, Credit Card and Digital Lending extracts.',
                'icon' => '📘',
                'route' => 'loans.loan-book.index',
                'visible' => true,
            ],
            [
                'key' => 'loan-utilization',
                'name' => 'Loan Utilization',
                'description' => 'Executive view of loan portfolio utilization, performance and NPL by product.',
                'icon' => '📈',
                'route' => 'loans.loan-utilization.index',
                'visible' => true,
            ],
            [
                'key' => 'admin-roles',
                'name' => 'Users & Roles',
                'description' => 'Assign or remove roles for any user, and manage the role list.',
                'icon' => '🛠️',
                'route' => 'admin.roles.index',
                'visible' => $user->hasRole('admin'),
            ],
        ];

        return view('home', compact('user', 'modules'));
    }
}
