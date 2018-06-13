<?php

namespace App\Commands;

use App\Models\Import;

trait CheckTheImportTrait
{

    public function shouldFinishImport(int $waitInMinutes = 180, string $errorMessage = null): bool
    {
        // Let's check, probably importing is working
        $lastImport = Import::whereSource(self::SOURCE)
            ->where('description', self::DESCRIPTION)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastImport AND $lastImport->status === Import::STATUS_STARTED) {
            //Let's check delay
            $delta = $lastImport->created_at->diffInMinutes();
            if ($delta < $waitInMinutes) {
                $message = 'Importing for source [' . self::SOURCE . '] (' . self::DESCRIPTION . ') should be finished.';
                if ($errorMessage) {
                    $message .= ' Message: ' . $errorMessage;
                }
                \Log::alert($message);
                return true;
            }
        }

        return false;
    }

}