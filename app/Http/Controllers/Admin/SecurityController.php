<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SecurityService;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __construct(
        protected SecurityService $securityService
    ) {}

    public function dashboard(): View
    {
        $securityData = $this->securityService->getSecurityDashboard();
        $securityScore = $securityData['security_score'] ?? 95;
        $suspiciousLogs = $this->securityService->getSuspiciousLogs(10);

        return view('admin.security.dashboard', compact('securityScore', 'suspiciousLogs'));
    }
}
