<?php

namespace App\Enums;

enum LoyaltyTier: int
{
    case Bronze = 0;
    case Silver = 1;
    case Gold = 2;
    case VIP = 3;

    public function label(): string
    {
        return match ($this) {
            self::Bronze => 'Bronze',
            self::Silver => 'Silver',
            self::Gold => 'Gold',
            self::VIP => 'VIP',
        };
    }

    public function multiplier(): float
    {
        return match ($this) {
            self::Bronze => 1.0,
            self::Silver => 1.5,
            self::Gold => 2.0,
            self::VIP => 3.0,
        };
    }

    public function minPoints(): int
    {
        return match ($this) {
            self::Bronze => 0,
            self::Silver => 500,
            self::Gold => 1500,
            self::VIP => 3000,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bronze => 'warning',
            self::Silver => 'gray',
            self::Gold => 'success',
            self::VIP => 'primary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Bronze => 'heroicon-o-star',
            self::Silver => 'heroicon-o-shield-check',
            self::Gold => 'heroicon-o-trophy',
            self::VIP => 'heroicon-o-sparkles',
        };
    }

    public function nextTier(): ?self
    {
        return match ($this) {
            self::Bronze => self::Silver,
            self::Silver => self::Gold,
            self::Gold => self::VIP,
            self::VIP => null,
        };
    }

    public static function fromPoints(int $lifetimePoints): self
    {
        return match (true) {
            $lifetimePoints >= 3000 => self::VIP,
            $lifetimePoints >= 1500 => self::Gold,
            $lifetimePoints >= 500 => self::Silver,
            default => self::Bronze,
        };
    }
}
