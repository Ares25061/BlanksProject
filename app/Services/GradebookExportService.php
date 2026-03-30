<?php

namespace App\Services;

use App\Models\StudentGroup;
use App\Support\SimpleXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GradebookExportService
{
    public function __construct(
        private StudentGradeService $studentGradeService,
    )
    {
    }

    public function exportMonth(StudentGroup $group, ?string $subjectName, string $month): array
    {
        try {
            $monthDate = CarbonImmutable::createFromFormat('Y-m', trim($month))->startOfMonth();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'month' => 'Месяц экспорта должен быть в формате ГГГГ-ММ.',
            ]);
        }

        $gradebook = $this->studentGradeService->buildGradebook($group, $subjectName);
        $dates = [];
        $cursor = $monthDate;
        $monthEnd = $monthDate->endOfMonth();

        while ($cursor->lte($monthEnd)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->addDay();
        }

        $rows = [
            ['Журнал группы', $gradebook['group']['name'] ?? ''],
            ['Предмет', $gradebook['subject_name'] ?: 'Без предмета'],
            ['Месяц', $monthDate->locale('ru')->translatedFormat('F Y')],
            [],
            array_merge(
                ['Студент'],
                array_map(fn (string $date) => CarbonImmutable::parse($date)->format('d.m'), $dates),
            ),
        ];

        foreach ($gradebook['students'] as $student) {
            $rows[] = array_merge(
                [$student['full_name']],
                array_map(
                    fn (string $date) => $student['grades'][$date]['grade_value'] ?? '',
                    $dates,
                ),
            );
        }

        return [
            'path' => SimpleXlsx::writeWorkbook('Журнал', $rows),
            'filename' => $this->buildFileName(
                $gradebook['group']['name'] ?? 'Группа',
                $gradebook['subject_name'] ?: 'Журнал',
                $monthDate->format('Y-m'),
            ),
        ];
    }

    private function buildFileName(string $groupName, string $subjectName, string $month): string
    {
        $value = sprintf('Журнал_%s_%s_%s.xlsx', $groupName, $subjectName, $month);

        return Str::of($value)
            ->replaceMatches('/[\\\\\\/:*?"<>|]+/u', ' ')
            ->squish()
            ->replace(' ', '_')
            ->value();
    }
}
