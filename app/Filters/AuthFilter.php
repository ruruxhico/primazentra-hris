<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    private const TIMEOUT_DEFAULT = 15 * 60; // 15 minutes of inactivity

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $lastActivity = session()->get('last_activity');

        // Sessions created before activity tracking was added — force re-login once
        if ($lastActivity === null) {
            session()->destroy();
            return redirect()->to('/auth/login')->with('error', 'Your session has expired. Please log in again.');
        }

        if ((time() - $lastActivity) > self::TIMEOUT_DEFAULT) {
            session()->destroy();
            return redirect()->to('/auth/login')->with('error', 'Your session has expired after 15 minutes of inactivity. Please log in again.');
        }

        // Sliding window — reset inactivity clock on every page visit
        session()->set('last_activity', time());

        // If user must change their temporary password, redirect to change-password page
        if (session()->get('force_change_password')) {
            return redirect()->to('/change-password');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
