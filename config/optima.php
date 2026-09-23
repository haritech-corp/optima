<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OPTIMA ERP application configuration
    |--------------------------------------------------------------------------
    |
    | Central place for blueprint configuration: modules, roles, service lines
    | and automation thresholds.
    */

    'company' => 'PT Optima Karya Global Group',

    'modules' => [
        'dashboard' => 'Dashboard',
        'admin' => 'Admin',
        'crm' => 'CRM',
        'sales' => 'Sales / Pipeline',
        'project' => 'Progress Project',
        'inventory' => 'Inventory & GA',
        'finance' => 'Finance',
        'hr' => 'SDM / HR',
    ],

    'roles' => [
        'super_admin_global' => 'Super Admin Global',
        'super_admin_department' => 'Super Admin Department',
        'koordinator' => 'Kepala/Koordinator',
        'staff' => 'Staff/Associate',
        'viewer' => 'Viewer',
    ],

    'service_lines' => [
        'PR' => 'Public Relation',
        'KOL' => 'KOL & KOC Marketing',
        'OOH' => 'OOH Advertising',
        'PMS' => 'Programmatic & Social Media Ads',
        'WAD' => 'Website & Apps Development',
        'CRP' => 'Creative Production',
    ],

    'notification_channels' => ['in_app', 'email', 'make_webhook'],

    'automation' => [
        'task_reminder_hours' => [72, 24],          // H-3 and H-1
        'overdue_escalation_hours' => 48,
        'budget_threshold_percent' => 90,
        'invoice_due_reminder_days' => 7,
        'approval_reminder_days' => 2,
    ],
];