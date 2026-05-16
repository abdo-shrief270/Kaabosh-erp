<?php

return [
    'success' => [
        'created' => 'Created successfully.',
        'updated' => 'Updated successfully.',
        'deleted' => 'Deleted successfully.',
        'restored' => 'Restored successfully.',
        'sent' => 'Sent successfully.',
        'approved' => 'Approved successfully.',
        'rejected' => 'Rejected.',
        'cancelled' => 'Cancelled.',
    ],

    'error' => [
        'not_found' => 'Item not found.',
        'model_not_found' => ':model not found.',
        'unauthorized' => 'Unauthorized. Please log in.',
        'forbidden' => 'You do not have permission for this action.',
        'validation' => 'The given data was invalid.',
        'too_many_requests' => 'Too many requests. Please wait a moment.',
        'tenant_not_found' => 'Tenant not found.',
        'tenant_not_accessible' => 'Tenant account is not accessible.',
        'server_error' => 'Server error. Please try again later.',
        'ip_blocked' => 'Access denied. Your IP address is not whitelisted.',
        'two_factor_required' => 'Please enter your two-factor authentication code.',
        'two_factor_invalid' => 'Invalid two-factor authentication code.',
        'password_breached' => 'This password has appeared in a data breach. Please choose a different password.',
        'duplicate_name' => 'This name is already in use.',
    ],

    'auth' => [
        'registered' => 'Registered successfully.',
        'logged_in' => 'Logged in successfully.',
        'logged_out' => 'Logged out successfully.',
        'invalid_credentials' => 'Invalid credentials.',
    ],

    'client' => [
        'deleted' => 'Client deleted successfully.',
        'portal_user_revoked' => 'Portal access revoked.',
        'portal_invite_resent' => 'Invite email resent.',
    ],

    'invoice' => [
        'sent' => 'Invoice sent successfully.',
        'cancelled' => 'Invoice cancelled.',
        'posted_to_gl' => 'Invoice posted to general ledger.',
        'credit_note_created' => 'Credit note created.',
    ],

    'payment' => [
        'recorded' => 'Payment recorded successfully.',
        'failed' => 'Payment failed. Please try again.',
    ],

    'team' => [
        'invited' => 'Invitation sent successfully.',
        'updated' => 'Team member updated.',
        'removed' => 'Team member removed successfully.',
    ],

    'payroll' => [
        'calculated' => 'Payroll calculated.',
        'approved' => 'Payroll approved.',
        'paid' => 'Payroll marked as paid.',
    ],
];
