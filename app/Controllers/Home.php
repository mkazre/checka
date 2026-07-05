<?php

namespace App\Controllers;

/**
 * Landing route. Sends visitors to the dashboard (or login if not authed).
 */
class Home extends BaseController
{
    public function index()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('dashboard');
        }

        return redirect()->to('login');
    }
}
