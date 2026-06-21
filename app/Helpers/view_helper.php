<?php

if (!function_exists('rr_badge')) {
    function rr_badge(string $status): string {
        $map = [
            'pending'                      => 'pz-badge-pending',
            'pending secondary'            => 'pz-badge-pending-secondary',
            'pending secondary approval'   => 'pz-badge-pending-secondary',
            'approved'                     => 'pz-badge-approved',
            'rejected'                     => 'pz-badge-rejected',
            'completed'                    => 'pz-badge-completed',
        ];
        $cls = $map[strtolower($status)] ?? 'pz-badge-pending';
        return '<span class="pz-badge ' . $cls . '">' . esc(strtoupper($status)) . '</span>';
    }
}

if (!function_exists('rr_url')) {
    function rr_url(array $params): string {
        $qs = http_build_query(array_filter($params, fn($v) => $v !== ''));
        return base_url('requests') . ($qs ? '?' . $qs : '');
    }
}
