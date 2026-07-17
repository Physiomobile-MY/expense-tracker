<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\ExpenseRecord;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        $query = $this->query($request);

        return view('reports.index', [
            'records' => (clone $query)->latest()->paginate(20)->withQueryString(),
            'summary' => [
                'count' => (clone $query)->count(),
                'amount' => (clone $query)->sum('total_amount'),
                'claimable' => (clone $query)->where('record_type', ExpenseRecord::TYPE_CLAIMABLE)->sum('total_amount'),
                'non_claimable' => (clone $query)->where('record_type', ExpenseRecord::TYPE_NON_CLAIMABLE)->sum('total_amount'),
            ],
            'departments' => Department::where('status', 'active')->orderBy('name')->get(),
            'categories' => ExpenseCategory::where('status', 'active')->orderBy('name')->get(),
            'staff' => User::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse|StreamedResponse
    {
        abort_unless($request->user()->canManageExpenses(), 403);

        $format = $request->validate([
            'format' => ['required', 'in:xlsx,csv,pdf'],
        ])['format'];

        $records = $this->query($request)
            ->with(['user', 'department', 'category', 'approvals.approver'])
            ->latest()
            ->get();

        $filename = 'physiomobile-expenses-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf', ['records' => $records])->download($filename.'.pdf');
        }

        $rows = $this->exportRows($records);

        if ($format === 'csv') {
            return $this->csvResponse($rows, $filename.'.csv');
        }

        return $this->xlsxResponse($rows, $filename.'.xlsx');
    }

    private function query(Request $request): Builder
    {
        return ExpenseRecord::query()
            ->with(['user', 'department', 'category'])
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('receipt_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('receipt_date', '<=', $request->date_to))
            ->when($request->filled('staff_id'), fn ($query) => $query->where('user_id', $request->staff_id))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->department_id))
            ->when($request->filled('expense_category_id'), fn ($query) => $query->where('expense_category_id', $request->expense_category_id))
            ->when($request->filled('record_type'), fn ($query) => $query->where('record_type', $request->record_type))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when(! $request->filled('status'), fn ($query) => $query->withoutVoided())
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', 'like', '%'.$request->payment_method.'%'));
    }

    private function exportRows($records): array
    {
        $rows = [[
            'Reference No',
            'Record Type',
            'Staff Name',
            'Department',
            'Merchant',
            'Receipt Date',
            'Category',
            'Claim Type',
            'Distance KM',
            'Mileage Rate',
            'Mileage Amount',
            'Toll Amount',
            'Toll Breakdown',
            'Parking Amount',
            'Amount',
            'Payment Method',
            'Status',
            'Submitted Date',
            'Approved Date',
            'Paid Date',
            'Recorded Date',
            'Approver',
            'Remarks',
        ]];

        foreach ($records as $record) {
            $approval = $record->approvals->where('action', 'approved')->last();

            $rows[] = [
                $record->claim_reference_no,
                $record->recordTypeLabel(),
                $record->user?->name,
                $record->department?->name,
                $record->merchant_name,
                $record->receipt_date?->format('Y-m-d'),
                $record->category?->name,
                $record->claimExpenseTypeLabel(),
                $record->route_distance_km,
                $record->mileage_rate,
                $record->mileage_amount,
                $record->toll_amount,
                $this->tollBreakdown($record->toll_entries),
                $record->parking_amount,
                $record->total_amount,
                $record->payment_method,
                $record->statusLabel(),
                $record->submitted_at?->format('Y-m-d H:i'),
                $record->approved_at?->format('Y-m-d H:i'),
                $record->paid_at?->format('Y-m-d H:i'),
                $record->recorded_at?->format('Y-m-d H:i'),
                $approval?->approver?->name,
                $record->remarks,
            ];
        }

        return $rows;
    }

    private function tollBreakdown(?array $entries): string
    {
        return collect($entries ?? [])
            ->map(fn (array $entry): string => trim(($entry['label'] ?? 'Toll').' MYR '.number_format((float) ($entry['amount'] ?? 0), 2)))
            ->implode('; ');
    }

    private function csvResponse(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function xlsxResponse(array $rows, string $filename): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'expenseflow-xlsx-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheet($rows));
        $zip->close();

        return response()->download(
            $path,
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function xlsxSheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $xml .= '<row r="'.$excelRow.'">';

            foreach (array_values($row) as $columnIndex => $value) {
                $cell = $this->xlsxColumn($columnIndex).$excelRow;

                if (is_numeric($value) && $rowIndex > 0) {
                    $xml .= '<c r="'.$cell.'"><v>'.(float) $value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$this->xml((string) $value).'</t></is></c>';
                }
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private function xlsxColumn(int $index): string
    {
        $column = '';

        do {
            $column = chr(65 + ($index % 26)).$column;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $column;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xlsxWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Expense Records" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }
}
