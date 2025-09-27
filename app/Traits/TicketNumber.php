<?php

namespace App\Traits;

trait TicketNumber
{
    public function numberToLetters(int $number): string
    {
        $letters = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $letters = chr(65 + $remainder).$letters;
            $number = (int) (($number - $remainder) / 26);
        }

        return $letters;
    }

    public function generateTicketNumberFromId(int $userId): string
    {

        // Just for example, use actual DB ID or sequence number
        $prefixNumber = intdiv($userId, 100);
        $suffixNumber = $userId % 100;

        $letterPrefix = $this->numberToLetters($prefixNumber + 1); // So it starts from A

        return $letterPrefix.$suffixNumber.'-100';
    }
}
