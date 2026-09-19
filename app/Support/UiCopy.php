<?php

namespace App\Support;

class UiCopy
{
    public static function entryStatus(string $status): string
    {
        return match ($status) {
            'draft' => __('Draft'),
            'submitted' => __('Submitted'),
            'pending_approval' => __('Pending approval'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            default => __(str_replace('_', ' ', $status)),
        };
    }
}
