<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .summary {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .number {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .summary-item .label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e9ecef;
            border-radius: 3px;
        }
        .filters h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .filter-item {
            display: inline-block;
            margin-right: 20px;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .status-present { color: #28a745; font-weight: bold; }
        .status-absent { color: #dc3545; font-weight: bold; }
        .status-excused { color: #ffc107; font-weight: bold; }
        .status-late { color: #fd7e14; font-weight: bold; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <p>Generated on: {{ $generatedAt }}</p>
        <p>{{ config('app.name') }} - School Management System</p>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="number">{{ $summaryStats['total'] }}</div>
            <div class="label">Total Records</div>
        </div>
        <div class="summary-item">
            <div class="number status-present">{{ $summaryStats['present'] }}</div>
            <div class="label">Present</div>
        </div>
        <div class="summary-item">
            <div class="number status-absent">{{ $summaryStats['absent'] }}</div>
            <div class="label">Absent</div>
        </div>
        <div class="summary-item">
            <div class="number status-excused">{{ $summaryStats['excused'] }}</div>
            <div class="label">Excused</div>
        </div>
        <div class="summary-item">
            <div class="number status-late">{{ $summaryStats['late'] }}</div>
            <div class="label">Late</div>
        </div>
    </div>

    @if(array_filter($filters ?? []))
    <div class="filters">
        <h3>Applied Filters:</h3>
        @if($filters['department'] ?? null)
            <div class="filter-item"><strong>Department:</strong> {{ $filters['department'] }}</div>
        @endif
        @if($filters['date_from'] ?? null)
            <div class="filter-item"><strong>From:</strong> {{ $filters['date_from'] }}</div>
        @endif
        @if($filters['date_to'] ?? null)
            <div class="filter-item"><strong>To:</strong> {{ $filters['date_to'] }}</div>
        @endif
        @if($filters['status'] ?? null)
            <div class="filter-item"><strong>Status:</strong> {{ ucfirst($filters['status']) }}</div>
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Course</th>
                <th>Teacher</th>
                <th>Department</th>
                <th>Session Time</th>
                <th>Status</th>
                <th>Marked At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendanceRecords as $record)
            <tr>
                <td>{{ $record->session_date }}</td>
                <td>{{ $record->student_number }}</td>
                <td>{{ $record->student_name }}</td>
                <td>{{ $record->class_name }}</td>
                <td>{{ $record->course }}</td>
                <td>{{ $record->teacher_first_name }} {{ $record->teacher_last_name }}</td>
                <td>{{ $record->department }}</td>
                <td>{{ $record->start_time }} - {{ $record->end_time }}</td>
                <td class="status-{{ $record->status }}">{{ ucfirst($record->status) }}</td>
                <td>{{ $record->marked_at ? date('H:i:s', strtotime($record->marked_at)) : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; color: #666;">No attendance records found for the selected criteria.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This report contains {{ $summaryStats['total'] }} attendance record(s)</p>
        <p>Report generated by {{ config('app.name') }} on {{ $generatedAt }}</p>
    </div>
</body>
</html>