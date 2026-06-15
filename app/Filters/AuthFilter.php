<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    // Inactivity limits — no activity for this long = auto-logout
    private const TIMEOUT_DEFAULT     = 14 * 60 * 60;       // 14 hours (covers 7am–8pm office hours + buffer)
    private const TIMEOUT_REMEMBER_ME = 30 * 24 * 60 * 60;  // 30 days  (remember me users)

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

        $isRememberMe = $request->getCookie('remember_me') === '1';
        $timeout      = $isRememberMe ? self::TIMEOUT_REMEMBER_ME : self::TIMEOUT_DEFAULT;

        if ((time() - $lastActivity) > $timeout) {
            session()->destroy();
            $message = $isRememberMe
                ? 'Your session has expired after 30 days of inactivity. Please log in again.'
                : 'Your session has expired after 8 hours of inactivity. Please log in again.';
            return redirect()->to('/auth/login')->with('error', $message);
        }

        // Sliding window — reset inactivity clock on every page visit
        session()->set('last_activity', time());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
