<?php

namespace App\Support;

use Carbon\Carbon;
use UnexpectedValueException;

/**
 * Validates feedback rows read from a Google Sheet before anything is stored.
 * Expected columns: A timestamp (d/m/Y H:i:s), B name, C type, D dish/product, E comments, F rating.
 */
class SheetFeedbackRows
{
    /**
     * @param  mixed  $data  values returned by the Sheets API
     * @param  callable(array): array  $mapColumns  maps columns B-F to model attributes
     * @return array<int, array> attributes per row, including feedback_date
     */
    public static function map(mixed $data, callable $mapColumns): array
    {
        if (!is_array($data) || $data === [] || !array_is_list($data)) {
            throw new UnexpectedValueException('The feedback sheet returned no rows.');
        }

        $rows = [];
        foreach ($data as $index => $row) {
            $sheetRow = $index + 2; // data starts at row 2

            if (!is_array($row) || count($row) < 6) {
                throw new UnexpectedValueException("Feedback sheet row {$sheetRow} is incomplete.");
            }

            if (!is_numeric($row[5]) || $row[5] < 1 || $row[5] > 5) {
                throw new UnexpectedValueException("Feedback sheet row {$sheetRow} has an invalid rating.");
            }

            try {
                $date = Carbon::createFromFormat('d/m/Y H:i:s', (string) $row[0]);
            } catch (\Throwable $e) {
                $date = false;
            }

            if (!$date) {
                throw new UnexpectedValueException("Feedback sheet row {$sheetRow} has an invalid date.");
            }

            $rows[] = $mapColumns($row) + ['feedback_date' => $date];
        }

        return $rows;
    }
}
