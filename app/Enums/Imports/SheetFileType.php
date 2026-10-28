<?php

namespace App\Enums\Imports;

use App\Concerns\ProvidesOptions;
use App\Contracts\LabelledEnum;

/**
 * A spreadsheet format the import engine accepts.
 *
 * Detected from the file's magic bytes: a `.xlsx` is a ZIP (`PK\x03\x04`) and a
 * legacy `.xls` is an OLE2 compound file (`\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1`). A
 * workbook renamed to the wrong extension is still read correctly; a document
 * that merely shares a ZIP signature is caught later, when the reader finds no
 * header row in it.
 */
enum SheetFileType: string implements LabelledEnum
{
    use ProvidesOptions;

    case Xlsx = 'xlsx';
    case Xls = 'xls';

    public static function id(): string
    {
        return 'sheet-file-type';
    }

    public function label(): string
    {
        return match ($this) {
            self::Xlsx => __('XLSX'),
            self::Xls => __('XLS'),
        };
    }

    public function magicBytes(): string
    {
        return match ($this) {
            self::Xlsx => "PK\x03\x04",
            self::Xls => "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1",
        };
    }

    /**
     * Detect the format of a file from its leading bytes.
     *
     * Returns `null` when the file is neither, which the import request turns
     * into a refusal naming the accepted formats.
     */
    public static function detect(string $path): ?self
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        $head = (string) fread($handle, 8);
        fclose($handle);

        foreach (self::cases() as $type) {
            if (str_starts_with($head, $type->magicBytes())) {
                return $type;
            }
        }

        return null;
    }
}
