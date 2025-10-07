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

public function generateNextTicketNumber(?int $userId = null): string
{
    // If user id not provided, try auth()
    if (empty($userId)) {
        $userId = auth()->id() ?? 0;
    }

    // Defensive: ensure integer
    $userId = (int) $userId;

    // Existing helper to convert number -> letters
    $prefixNumber = intdiv($userId, 100);
    $suffixNumber = $userId % 100;

    // convert 0-based prefix -> letters and ensure non-empty prefix
    $letterPrefix = $this->numberToLetters(max(0, $prefixNumber) + 1);

    // Example suffix formatting — adjust to your system if needed:
    $suffixNumber = str_pad((string)$suffixNumber, 2, '0', STR_PAD_LEFT);

    // append sequence: this function only generates prefix+suffix.
    // The final sequence (like -122) should be generated elsewhere or via DB sequence.
    // For now we return a candidate that matches your pattern: A32-<next>
    // You may want to use DB to compute next numeric suffix (see notes below).
    return "{$letterPrefix}{$suffixNumber}-" . now()->format('z'); // temporary deterministic tail
}

}
