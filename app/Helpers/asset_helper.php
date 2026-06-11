<?php

if (! function_exists('asset_url')) {
    /**
     * Returns the correct public URL for a static asset (image, CSS, JS, etc.)
     * regardless of whether the entry point is public/index.php (production)
     * or the root index.php (XAMPP localhost).
     *
     * On production, FCPATH === public/, so assets live at base_url('assets/...')
     * On XAMPP with root index.php, FCPATH === project root, so assets live at base_url('public/assets/...')
     */
    function asset_url(string $path): string
    {
        $path = ltrim($path, '/');
        $prefix = is_file(FCPATH . $path) ? '' : 'public/';
        return base_url($prefix . $path);
    }
}
