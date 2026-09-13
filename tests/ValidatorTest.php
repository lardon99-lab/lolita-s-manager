<?php
declare(strict_types=1);

use App\Http\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testPositiveIntegerRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::positiveInt(0, 'cantidad');
    }

    public function testMoneyRoundsAndRejectsNegativeValues(): void
    {
        self::assertSame(12.35, Validator::money('12.345', 'monto'));
        $this->expectException(InvalidArgumentException::class);
        Validator::money('-0.01', 'monto');
    }

    public function testDateRequiresExactIsoFormat(): void
    {
        self::assertSame('2026-08-26', Validator::date('2026-08-26', 'fecha'));
        $this->expectException(InvalidArgumentException::class);
        Validator::date('26/08/2026', 'fecha');
    }

    public function testTextEnforcesMaximumLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::text('abcd', 'nombre', 3);
    }

    public function testDateTimeLocalAcceptsBrowserFormat(): void
    {
        self::assertSame('2026-09-12 14:30:00', Validator::dateTimeLocal('2026-09-12T14:30', 'fecha'));
    }

    public function testDateTimeLocalRejectsInvalidCalendarDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::dateTimeLocal('2026-02-30T10:00', 'fecha');
    }
}
