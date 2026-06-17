<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    private const TIMEOUT_STANDARD = 8  * 60 * 60;        // 8 hours  (no remember me)
    private const TIMEOUT_REMEMBER  = 30 * 24 * 60 * 60;  // 30 days  (remember me checked)

    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $loginTime  = session()->get('login_time');
        $rememberMe = (bool) session()->get('remember_me');
        $timeout    = $rememberMe ? self::TIMEOUT_REMEMBER : self::TIMEOUT_STANDARD;

        // Sessions created before login_time tracking — force a clean re-login once
        if ($loginTime === null) {
            session()->destroy();
            return redirect()->to('/auth/login')->with('error', 'Your session has expired. Please log in again.');
        }

        if ((time() - $loginTime) > $timeout) {
            session()->destroy();
            $msg = $rememberMe
                ? 'Your 30-day session has expired. Please log in again.'
                : 'Your session has expired after 8 hours. Please log in again.';
            return redirect()->to('/auth/login')->with('error', $msg);
        }

        if (session()->get('force_change_password')) {
            return redirect()->to('/change-password');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
