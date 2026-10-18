<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class UsersExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithCustomStartCell, WithColumnFormatting, WithCustomValueBinder, WithTitle
{
    private string $type;
    private array $columns;
    private int $rowCount = 0;
    private int $counter = 0;
    private bool $hasNfcCards = false;
    private bool $canExportSubjectClasses = false;
    private array $sectionFilters;
    private string $sheetTitle;

    public function __construct(
        string $type = 'all',
        ?array $columns = null,
        array $sectionFilters = [],
        string $sheetTitle = ''
    )
    {
        $this->type = self::normalizeType($type);
        $available = self::availableColumns($this->type);
        $this->columns = array_values(array_intersect(
            $columns ?? self::defaultColumns($this->type),
            array_keys($available)
        ));
        $this->sectionFilters = $sectionFilters;
        $this->sheetTitle = $sheetTitle !== '' ? $sheetTitle : strtoupper($this->type);
    }

    public static function normalizeType(string $type): string
    {
        return match (strtolower($type)) {
            'student' => 'students',
            'teacher' => 'faculty',
            'directors' => 'management',
            default => strtolower($type),
        };
    }

    public static function availableColumns(string $type): array
    {
        $common = [
            'account_id' => 'Account ID',
            'first_name' => 'First Name',
            'middle_name' => 'Middle Name',
            'last_name' => 'Last Name',
            'email' => 'Email Address',
            'username' => 'Username',
            'phone_number' => 'Contact Number',
        ];

        return match (self::normalizeType($type)) {
            'students' => [
                'student_id' => 'Student ID',
                'lrn' => 'LRN',
                'first_name' => 'First Name',
                'middle_name' => 'Middle Name',
                'last_name' => 'Last Name',
            ],
            'faculty' => [
                'account_id' => 'Employee ID',
                'first_name' => 'First Name',
                'middle_name' => 'Middle Name',
                'last_name' => 'Last Name',
                'email' => 'Email Address',
                'username' => 'Username',
                'phone_number' => 'Contact Number',
                'faculty_type' => 'Faculty Type',
                'advisory_grade' => 'Advisory Grade',
                'advisory_section' => 'Advisory Section',
                'assigned_sections' => 'Assigned Class Sections',
            ],
            'management', 'admin' => [
                ...$common,
                'department' => 'Department / Office',
                'status' => 'Account Status',
                'created_at' => 'Account Created',
            ],
            default => [
                'account_id' => 'Account ID',
                'first_name' => 'First Name',
                'middle_name' => 'Middle Name',
                'last_name' => 'Last Name',
                'email' => 'Email Address',
                'username' => 'Username',
                'account_type' => 'Account Type',
                'phone_number' => 'Contact Number',
                'status' => 'Account Status',
            ],
        };
    }

    public static function defaultColumns(string $type): array
    {
        return array_keys(self::availableColumns($type));
    }

    public function collection()
    {
        $role = match ($this->type) {
            'students' => ['id' => 3, 'names' => ['student']],
            'faculty' => ['id' => 2, 'names' => ['teacher', 'faculty']],
            'management' => ['id' => 4, 'names' => ['management', 'director']],
            'admin' => ['id' => 1, 'names' => ['admin', 'administrator']],
            default => null,
        };

        $relations = ['role'];
        $this->hasNfcCards = Schema::hasTable('nfc_cards');
        if ($this->hasNfcCards) {
            $relations[] = 'nfcCard';
        }
        $this->canExportSubjectClasses = $this->type === 'faculty'
            && in_array('assigned_sections', $this->columns, true)
            && Schema::hasTable('class_schedules')
            && Schema::hasTable('academic_sections');
        if ($this->canExportSubjectClasses) {
            $relations[] = 'classSchedules.academicSection';
        }

        $query = User::with($relations);
        if ($role) {
            $hasRoleColumn = Schema::hasColumn('users', 'role');
            $query->where(function ($query) use ($role, $hasRoleColumn) {
                $query->where('role_id', $role['id']);
                if ($hasRoleColumn) {
                    $query->orWhereIn('role', $role['names']);
                }
            });
        }

        if ($this->sectionFilters !== []) {
            $query->where(function ($query) {
                foreach ($this->sectionFilters as $index => $filter) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $query->{$method}(function ($sectionQuery) use ($filter) {
                        $sectionQuery->where('section', $filter['section_name']);
                        if ($filter['grade_level'] === '') {
                            $sectionQuery->where(function ($gradeQuery) {
                                $gradeQuery->whereNull('grade_level')->orWhere('grade_level', '');
                            });
                        } else {
                            $sectionQuery->where('grade_level', $filter['grade_level']);
                        }
                    });
                }
            });
        }

        $users = $query->orderBy('last_name')->orderBy('first_name')->get();
        $this->rowCount = $users->count();
        $this->counter = 0;

        return $users;
    }

    public function startCell(): string
    {
        return 'A5';
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function headings(): array
    {
        $available = self::availableColumns($this->type);

        return ['No.', ...array_map(fn (string $key) => $available[$key], $this->columns)];
    }

    public function map($user): array
    {
        $this->counter++;

        $facultyType = $user->section || $user->grade_level ? 'Adviser' : 'Subject Teacher';
        $assignedSections = '';
        if ($this->canExportSubjectClasses && in_array('assigned_sections', $this->columns, true)) {
            $assignedSections = $user->classSchedules
                ->map(function ($schedule) {
                    $section = $schedule->academicSection?->section_name ?? $schedule->section ?? 'Unassigned section';
                    $grade = $schedule->academicSection?->grade_level ?? $schedule->grade_level;

                    return trim(($grade ? "Grade {$grade} - " : '') . $section, ' -');
                })
                ->unique()
                ->values()
                ->implode("\n");
        }

        $values = [
            'account_id' => $user->id_number ?? $user->student_id ?? 'N/A',
            'student_id' => (string) ($user->student_id ?? 'N/A'),
            'lrn' => (string) ($user->id_number ?? 'N/A'),
            'first_name' => $user->first_name ?? $user->name ?? '',
            'middle_name' => $user->middle_name ?? '',
            'last_name' => $user->last_name ?? '',
            'email' => $user->email ?? 'N/A',
            'username' => $user->username ?? 'N/A',
            'account_type' => $user->role?->name ?? 'N/A',
            'phone_number' => $user->phone_number ?? 'N/A',
            'gender' => $user->gender_name ?? $user->gender ?? 'N/A',
            'grade_level' => $user->grade_level ?? 'N/A',
            'strand' => $user->strand ?? $user->track ?? 'N/A',
            'section' => $user->section ?? 'N/A',
            'parent_name' => $user->parent_name ?? 'N/A',
            'parent_relationship' => $user->parent_relationship ?? 'N/A',
            'parent_phone_number' => $user->parent_phone_number ?? 'N/A',
            'nfc_uid' => $this->hasNfcCards ? ($user->nfcCard?->tag_id ?? 'No Card') : 'No Card',
            'faculty_type' => $facultyType,
            'advisory_grade' => $facultyType === 'Adviser' ? ($user->grade_level ?? 'N/A') : 'N/A',
            'advisory_section' => $facultyType === 'Adviser' ? ($user->section ?? 'N/A') : 'N/A',
            'assigned_sections' => $assignedSections !== '' ? $assignedSections : 'No assigned class sections',
            'department' => $user->section ?? 'N/A',
            'status' => ($user->is_active ?? true) ? 'Active' : 'Inactive',
            'created_at' => $user->created_at?->format('Y-m-d H:i') ?? 'N/A',
        ];

        $selectedValues = array_map(fn (string $key) => $values[$key], $this->columns);

        return [$this->counter, ...$selectedValues];
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach ($this->columns as $index => $key) {
            if (in_array($key, ['account_id', 'student_id', 'lrn', 'username'], true)) {
                $formats[Coordinate::stringFromColumnIndex($index + 2)] = NumberFormat::FORMAT_TEXT;
            }
        }

        return $formats;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $columnIndex = Coordinate::columnIndexFromString($cell->getColumn());
        $columnKey = $this->columns[$columnIndex - 2] ?? null;
        if ($this->type === 'students' && in_array($columnKey, ['student_id', 'lrn'], true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
        $lastRow = 5 + $this->rowCount;

        $sheet->mergeCells("A1:{$lastColumn}1");
        $sheet->mergeCells("A2:{$lastColumn}2");
        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->setCellValue('A1', 'SIATRACK INSTITUTIONAL DIRECTORY');
        $sheet->setCellValue('A2', 'Category Records: ' . $this->sheetTitle);
        $sheet->setCellValue('A3', 'Generated On: ' . date('F d, Y - h:i A'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB('555555');

        $tableRange = "A5:{$lastColumn}" . max(5, $lastRow);
        $sheet->getStyle($tableRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'CBD5E1'],
                ],
            ],
        ]);
        $sheet->getStyle("A6:{$lastColumn}" . max(6, $lastRow))
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
        $sheet->freezePane('A6');
        $sheet->setAutoFilter($tableRange);
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(5)->setRowHeight(30);

        return [
            5 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '8B1818'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}
