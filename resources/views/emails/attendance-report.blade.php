<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .summary-item .label {
            font-size: 12px;
            color: #666;
        }
        .filters {
            margin: 20px 0;
            padding: 15px;
            background-color: #fff3cd;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Attendance Report</h1>
        <p>{{ config('app.name') }} - School Management System</p>
        <p>Generated on: {{ now()->format('F j, Y \a\t g:i A') }}</p>
    </div>

    <h2>Report Summary</h2>
    <div class="summary">
        <div class="summary-item">
            <div class="number">{{ $summaryStats['total'] }}</div>
            <div class="label">Total Records</div>
        </div>
        <div class="summary-item">
            <div class="number" style="color: #28a745;">{{ $summaryStats['present'] }}</div>
            <div class="label">Present</div>
        </div>
        <div class="summary-item">
            <div class="number" style="color: #dc3545;">{{ $summaryStats['absent'] }}</div>
            <div class="label">Absent</div>
        </div>
        <div class="summary-item">
            <div class="number" style="color: #ffc107;">{{ $summaryStats['excused'] }}</div>
            <div class="label">Excused</div>
        </div>
        <div class="summary-item">
            <div class="number" style="color: #fd7e14;">{{ $summaryStats['late'] }}</div>
            <div class="label">Late</div>
        </div>
    </div>

    @if(array_filter($filters ?? []))
    <div class="filters">
        <h3>Applied Filters:</h3>
        <ul>
            @if($filters['department'] ?? null)
                <li><strong>Department:</strong> {{ $filters['department'] }}</li>
            @endif
            @if($filters['date_from'] ?? null)
                <li><strong>From Date:</strong> {{ $filters['date_from'] }}</li>
            @endif
            @if($filters['date_to'] ?? null)
                <li><strong>To Date:</strong> {{ $filters['date_to'] }}</li>
            @endif
            @if($filters['status'] ?? null)
                <li><strong>Status:</strong> {{ ucfirst($filters['status']) }}</li>
            @endif
        </ul>
    </div>
    @endif

    <h2>Report Details</h2>
    <p>The detailed attendance report is attached as a PDF file. This report contains all attendance records matching your selected criteria.</p>

    <p>The PDF includes:</p>
    <ul>
        <li>Complete attendance records with student details</li>
        <li>Class and teacher information</li>
        <li>Session times and attendance status</li>
        <li>Summary statistics</li>
    </ul>

    <div class="footer">
        <p>This is an automated email from {{ config('app.name') }}.</p>
        <p>If you have any questions about this report, please contact the system administrator.</p>
    </div>
</body>
</html>