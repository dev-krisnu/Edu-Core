<?php
/** Map DB role to CSS data-role theme key */
function getRoleThemeKey(?string $role): string
{
    return match ($role) {
        'super_admin' => 'admin',
        'librarian'   => 'library',
        'tpo'         => 'placements',
        default       => $role ?? 'student',
    };
}
