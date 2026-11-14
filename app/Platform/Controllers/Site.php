<?php

namespace Platform\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use Platform\Services\Billing;
use Platform\Services\PlatformSettings;

/**
 * Serves the marketing website (repo folder site/) on the bare domain and www.
 * Clean URLs (/pricing) and the original file names (Pricing.html) both work.
 * config.js and sitemap.xml are adjusted to the configured root domain, so the
 * same files work on checkaplus.co.za and on a local test domain.
 */
class Site extends Controller
{
    private const PAGES = ['' => 'Home', 'home' => 'Home', 'features' => 'Features', 'pricing' => 'Pricing', 'industries' => 'Industries',
        'about' => 'About', 'contact' => 'Contact', 'signup' => 'SignUp', 'sign-up' => 'SignUp', 'login' => 'Login'];

    private const TYPES = ['html' => 'text/html; charset=utf-8', 'js' => 'application/javascript; charset=utf-8', 'css' => 'text/css; charset=utf-8',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'webp' => 'image/webp', 'ico' => 'image/x-icon',
        'xml' => 'application/xml; charset=utf-8', 'txt' => 'text/plain; charset=utf-8', 'woff2' => 'font/woff2'];

    public function page(...$segments)
    {
        $path = trim(implode('/', $segments), '/');
        $key  = strtolower($path);

        if (isset(self::PAGES[$key])) {
            $path = self::PAGES[$key] . '.html';
        } elseif ($key === 'robots.txt') {
            return $this->response->setContentType('text/plain')->setBody("User-agent: *\nAllow: /\nSitemap: " . $this->origin() . "/sitemap.xml\n");
        }

        $dir  = realpath(ROOTPATH . 'site');
        $file = $dir ? realpath($dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path)) : false;
        $ext  = strtolower(pathinfo((string) $file, PATHINFO_EXTENSION));
        if (! $file || ! str_starts_with($file, $dir . DIRECTORY_SEPARATOR) || ! is_file($file) || ! isset(self::TYPES[$ext])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $body = file_get_contents($file);
        if ($path === 'config.js' || $path === 'sitemap.xml' || $ext === 'html') {
            $body = $this->localise($body);
        }
        $cache = in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp', 'ico', 'woff2'], true) ? 'public, max-age=604800' : 'public, max-age=300';

        return $this->response->setHeader('Content-Type', self::TYPES[$ext])->setHeader('Cache-Control', $cache)->setBody($body);
    }

    /** Point the site's URLs at the configured domain (and local port when testing). */
    private function localise(string $body): string
    {
        $root = (string) PlatformSettings::value('general', 'rootDomain', 'checkaplus.co.za');
        $base = config('App')->baseURL;
        $port = parse_url($base, PHP_URL_PORT);
        $sch  = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $sfx  = $port ? ':' . $port : '';

        return strtr($body, [
            'https://app.checkaplus.co.za' => rtrim(Billing::appUrl(), '/'),
            'https://www.checkaplus.co.za' => $this->origin(),
            'https://checkaplus.co.za'     => $this->origin(),
            "'.checkaplus.co.za'"          => "'." . $root . $sfx . "'",
            "'https://'"                   => "'" . $sch . "://'",
            '.checkaplus.co.za'            => '.' . $root,
        ]);
    }

    private function origin(): string
    {
        $base = config('App')->baseURL;
        $port = parse_url($base, PHP_URL_PORT);

        return (parse_url($base, PHP_URL_SCHEME) ?: 'https') . '://' . PlatformSettings::value('general', 'rootDomain', 'checkaplus.co.za') . ($port ? ':' . $port : '');
    }
}
