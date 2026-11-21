<?php

declare(strict_types=1);

namespace Library\Entity;

enum Role: string
{
    case Student    = 'student';
    case Operator   = 'operator';
    case SuperAdmin = 'super_admin';

    public function isStaff(): bool
    {
        return $this !== self::Student;
    }
}
