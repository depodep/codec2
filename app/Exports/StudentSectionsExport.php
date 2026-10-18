<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentSectionsExport implements WithMultipleSheets
{
    public function __construct(
        private array $columns,
        private array $sections
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];
        $usedTitles = [];

        foreach ($this->sections as $section) {
            $title = $this->uniqueTitle($section['label'], $usedTitles);
            $sheets[] = new UsersExport(
                'students',
                $this->columns,
                [$section],
                $title
            );
        }

        $sheets[] = new UsersExport(
            'students',
            $this->columns,
            $this->sections,
            $this->uniqueTitle('Master List', $usedTitles)
        );

        return $sheets;
    }

    private function uniqueTitle(string $title, array &$usedTitles): string
    {
        $title = trim(preg_replace('/[\[\]:*?\/\\\\]/u', ' ', $title));
        $title = mb_substr($title !== '' ? $title : 'Section', 0, 31);
        $candidate = $title;
        $suffix = 2;

        while (in_array(mb_strtolower($candidate), $usedTitles, true)) {
            $suffixText = " ({$suffix})";
            $candidate = mb_substr($title, 0, 31 - mb_strlen($suffixText)) . $suffixText;
            $suffix++;
        }

        $usedTitles[] = mb_strtolower($candidate);

        return $candidate;
    }
}
