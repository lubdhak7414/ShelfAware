<?php
require_once __DIR__ . '/config.php';

/**
 * Escape a string for HTML output.
 */
function e(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirect to a URL and exit.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Require a logged-in member. Redirects to login page if not authenticated.
 */
function require_login(): void
{
    if (empty($_SESSION['member_id'])) {
        redirect('/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Require a logged-in staff member, optionally matching a specific role.
 * Redirects to staff login if not authenticated.
 */
function require_staff(?string $role = null): void
{
    if (empty($_SESSION['staff_id'])) {
        redirect('/staff_login.php');
    }
    if ($role !== null && ($_SESSION['staff_role'] ?? '') !== $role) {
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>';
        exit;
    }
}

/**
 * Return a human-readable relative path for assets (no hardcoded host).
 */
function asset(string $path): string
{
    return '/' . ltrim($path, '/');
}
