<?php
declare(strict_types=1);

namespace App\Security;

final class OrderStatusPolicy
{
    public const PENDING = 'Pendiente';
    public const FINISHED = 'Terminado';
    public const DELIVERED = 'Entregado';
    public const LEGACY_CANCELLED = 'Cancelado';

    /** @return array<string, string> target state => required permission */
    public static function transitions(string $currentState): array
    {
        return match ($currentState) {
            self::PENDING => [self::FINISHED => 'orders.finish'],
            self::FINISHED => [self::DELIVERED => 'orders.deliver'],
            default => [],
        };
    }

    public static function requiredPermission(string $currentState, string $targetState): ?string
    {
        return self::transitions($currentState)[$targetState] ?? null;
    }

    public static function roleCanTransition(int $role, string $currentState, string $targetState): bool
    {
        return match ([$currentState, $targetState]) {
            [self::PENDING, self::FINISHED] => in_array($role, [Auth::ADMIN, Auth::SUPERUSER], true),
            [self::FINISHED, self::DELIVERED] => in_array($role, [Auth::EMPLOYEE, Auth::SUPERUSER], true),
            default => false,
        };
    }

    /** @return list<string> */
    public static function filterStates(): array
    {
        return ['Todos', self::PENDING, self::FINISHED, self::DELIVERED];
    }

    public static function defaultFilterState(int $role): string
    {
        return $role === Auth::EMPLOYEE ? self::FINISHED : self::PENDING;
    }
}
