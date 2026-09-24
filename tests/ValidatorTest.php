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

    public function testIntegerRangeRejectsPartiallyNumericValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::intRange('12abc', 'cantidad', 0, 100);
    }

    public function testIdListIsStrictAndRemovesDuplicates(): void
    {
        self::assertSame([2, 3], Validator::idList(['2', '3', '2'], 'sucursales'));
    }

    public function testPhoneAndEmailNormalizeOptionalValues(): void
    {
        self::assertNull(Validator::phone(''));
        self::assertSame('+504 9999-9999', Validator::phone('+504 9999-9999'));
        self::assertSame('persona@example.com', Validator::email(' Persona@Example.COM '));
    }

    public function testDateRangeRejectsReverseOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::dateRange('2026-09-24', '2026-09-23');
    }

    public function testPositiveMoneyRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Validator::positiveMoney('0', 'monto');
    }

    public function testSingleLineTextNormalizesRepeatedWhitespace(): void
    {
        self::assertSame('Pastel Tres Leches', Validator::singleLineText("  Pastel\n  Tres   Leches  ", 'producto', 100));
    }
}
