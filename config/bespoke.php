<?php

$configuredSuperAdmins = array_values(array_filter(array_map(
    static fn (string $email): string => strtolower(trim($email)),
    explode(',', (string) env(
        'BESPOKE_SUPER_ADMIN_EMAILS',
        'sony@bespokeadvertising.com.mx,marco@bespokeadvertising.com.mx'
    ))
)));

return [
    'super_admin_emails' => $configuredSuperAdmins,
];
