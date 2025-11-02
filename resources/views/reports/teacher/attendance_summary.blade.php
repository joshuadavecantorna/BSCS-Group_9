<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Summary Report</title>
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
        .header .subtitle {
            margin: 5px 0;
            color: #666;
        }
        .summary-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
            flex: 1;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-label {
            color: #6b7280;
            font-size: 11px;
            margin-top: 5px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0 10px 0;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .status-present { color: #10b981; font-weight: bold; }
        .status-absent { color: #ef4444; font-weight: bold; }
        .status-late { color: #f59e0b; font-weight: bold; }
        .status-excused { color: #6b7280; font-weight: bold; }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 10px;
        }
        .class-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .class-header {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Summary Report</h1>
        <div class="subtitle">Generated on {{ $generatedAt }}</div>
    </div>

    @if($data->isNotEmpty())
        @php
            $grouped = $data->groupBy('class_name');
            $totalRecords = $data->count();
            $presentCount = $data->where('status', 'present')->count();
            $absentCount = $data->where('status', 'absent')->count();
            $lateCount = $data->where('status', 'late')->count();
            $excusedCount = $data->where('status', 'excused')->count();
            $attendanceRate = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;
        @endphp

        <div class="summary-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $totalRecords }}</div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-item">
                <div class="stat-value status-present">{{ $presentCount }}</div>
                <div class="stat-label">Present</div>
            </div>
            <div class="stat-item">
                <div class="stat-value status-absent">{{ $absentCount }}</div>
                <div class="stat-label">Absent</div>
            </div>
            <div class="stat-item">
                <div class="stat-value status-late">{{ $lateCount }}</div>
                <div class="stat-label">Late</div>
            </div>
            <div class="stat-item">
                <div class="stat-value status-excused">{{ $excusedCount }}</div>
                <div class="stat-label">Excused</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $attendanceRate }}%</div>
                <div class="stat-label">Attendance Rate</div>
            </div>
        </div>

        @foreach($grouped as $className => $classData)
            <div class="class-section">
                <div class="class-header">
                    {{ $className }} 
                    @if($classData->first()->course)
                        ({{ $classData->first()->course }} - {{ $classData->first()->section }})
                    @endif
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Session</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classData->sortBy('student_name') as $record)
                            <tr>
                                <td>{{ $record->student_id }}</td>
                                <td>{{ $record->student_name }}</td>
                                <td>{{ $record->session_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($record->session_date)->format('M d, Y') }}</td>
                                <td class="status-{{ $record->status }}">
                                    {{ ucfirst($record->status) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div style="text-align: center; padding: 50px; color: #6b7280;">
            <h3>No Data Available</h3>
            <p>No attendance records found for the selected criteria.</p>
        </div>
    @endif

    <div class="footer">
        <p>This report was automatically generated by the Attendance Management System</p>
        <p>Report Type: Attendance Summary | Format: PDF | Generated: {{ $generatedAt }}</p>
    </div>
</body>
</html>