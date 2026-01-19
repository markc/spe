<?php declare(strict_types=1);

// Copyright (C) 2015-2026 Mark Constable <mc@netserva.org> (MIT License)

namespace SPE\App;

/**
 * Access Control Levels (from HCP pattern)
 *
 * Usage:
 *   Acl::SuperAdmin->value  // 0
 *   Acl::from(0)            // Acl::SuperAdmin
 *   Acl::SuperAdmin->label()  // 'Super Admin'
 *   Acl::SuperAdmin->can(Acl::Admin)  // true (higher can access lower)
 */
enum Acl: int
{
    case SuperAdmin = 0;
    case Admin = 1;
    case User = 2;
    case Suspended = 3;
    case Anonymous = 9;

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Administrator',
            self::User => 'User',
            self::Suspended => 'Suspended',
            self::Anonymous => 'Anonymous',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SuperAdmin => '👑',
            self::Admin => '🛡️',
            self::User => '👤',
            self::Suspended => '🚫',
            self::Anonymous => '👻',
        };
    }

    /**
     * Check if this ACL level can access resources requiring $required level
     * Lower number = higher privilege (SuperAdmin=0 can access everything)
     */
    public function can(self $required): bool
    {
        // Suspended and Anonymous can't access anything requiring auth
        if ($this === self::Suspended || $this === self::Anonymous) {
            return $required === self::Anonymous;
        }
        return $this->value <= $required->value;
    }

    /**
     * Get ACL from session, defaulting to Anonymous
     */
    public static function current(): self
    {
        $acl = $_SESSION['usr']['acl'] ?? 9;
        return self::tryFrom((int) $acl) ?? self::Anonymous;
    }

    /**
     * Check if current user has required access level
     */
    public static function check(self $required): bool
    {
        return self::current()->can($required);
    }

    /**
     * Get all ACL options for select dropdowns
     */
    public static function options(): array
    {
        return array_map(static fn(self $a) => [
            'value' => $a->value,
            'label' => $a->label(),
            'icon' => $a->icon(),
        ], self::cases());
    }
}
