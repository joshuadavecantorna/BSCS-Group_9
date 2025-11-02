<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Attendance Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #2563eb;
        }
        .student-card {
            border: 1px solid #d1d5db;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .student-header {
            background-color: #f8fafc;
            padding: 12px;
            border-bottom: 1px solid #d1d5db;
        }
        .student-name {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
        }
        .student-info {
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }
        .attendance-summary {
            display: flex;
            padding: 10px 12px;
            background-color: #fafbfc;
            border-bottom: 1px solid #e5e7eb;
        }
        .summary-item {
            flex: 1;
            text-align: center;
        }
        .summary-value {
            font-weight: bold;
            font-size: 14px;
        }
        .summary-label {
            font-size: 10px;
            color: #6b7280;
        }
        .attendance-records {
            padding: 10px;
        }
        .record-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px dotted #e5e7eb;
        }
        .record-date {
            font-weight: bold;
        }
        .record-class {
            color: #6b7280;
            font-size: 11px;
        }
        .record-status {
            font-weight: bold;
        }
        .status-present { color: #10b981; }
        .status-absent { color: #ef4444; }
        .status-late { color: #f59e0b; }
        .status-excused { color: #6b7280; }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Student Attendance Report</h1>
        <div style="margin: 5px 0; color: #666;">Generated on {{ $generatedAt }}</div>
    </div>

    @if($data->isNotEmpty())
        @php
            $grouped = $data->groupBy('student_name');
        @endphp

        @foreach($grouped as $studentName => $studentRecords)
            @php
                $firstRecord = $studentRecords->first();
                $totalRecords = $studentRecords->count();
                $presentCount = $studentRecords->where('status', 'present')->count();
                $absentCount = $studentRecords->where('status', 'absent')->count();
                $lateCount = $studentRecords->where('status', 'late')->count();
                $attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;
            @endphp

            <div class="student-card">
                <div class="student-header">
                    <div class="student-name">{{ $studentName }}</div>
                    <div class="student-info">
                        ID: {{ $firstRecord->student_id }} | 
                        Course: {{ $firstRecord->course ?? 'N/A' }} | 
                        Attendance Rate: {{ $attendanceRate }}%
                    </div>
                </div>

                <div class="attendance-summary">
                    <div class="summary-item">
                        <div class="summary-value">{{ $totalRecords }}</div>
                        <div class="summary-label">Total Sessions</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value status-present">{{ $presentCount }}</div>
                        <div class="summary-label">Present</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value status-absent">{{ $absentCount }}</div>
                        <div class="summary-label">Absent</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value status-late">{{ $lateCount }}</div>
                        <div class="summary-label">Late</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">{{ $attendanceRate }}%</div>
                        <div class="summary-label">Rate</div>
                    </div>
                </div>

                <div class="attendance-records">
                    @foreach($studentRecords->sortByDesc('session_date') as $record)
                        <div class="record-item">
                            <div>
                                <div class="record-date">
                                    {{ \Carbon\Carbon::parse($record->session_date)->format('M d, Y') }}
                                </div>
                                <div class="record-class">{{ $record->session_name }}</div>
                            </div>
                            <div class="record-status status-{{ $record->status }}">
                                {{ ucfirst($record->status) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @else
        <div class="no-data">
            <h3>No Data Available</h3>
            <p>No student attendance records found for the selected criteria.</p>
        </div>
    @endif
</body>
</html>