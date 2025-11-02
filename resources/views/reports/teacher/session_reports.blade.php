<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Session Reports</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .session { margin-bottom: 30px; page-break-inside: avoid; border: 1px solid #ddd; }
        .session-header { background: #f8f9fa; padding: 15px; font-weight: bold; }
        .session-stats { display: flex; padding: 10px 15px; background: #fff; }
        .stat { flex: 1; text-align: center; }
        .attendance-table { width: 100%; border-collapse: collapse; margin: 15px; }
        .attendance-table th, .attendance-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .status-present { color: #10b981; font-weight: bold; }
        .status-absent { color: #ef4444; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Session Reports</h1>
        <div>Generated on {{ $generatedAt }}</div>
    </div>

    @if($data->isNotEmpty())
        @foreach($data->groupBy('session_name') as $sessionName => $sessionData)
            <div class="session">
                <div class="session-header">
                    {{ $sessionName }} - {{ $sessionData->first()->session_date }}
                </div>
                <div class="session-stats">
                    <div class="stat">
                        <strong>{{ $sessionData->where('status', 'present')->count() }}</strong><br>Present
                    </div>
                    <div class="stat">
                        <strong>{{ $sessionData->where('status', 'absent')->count() }}</strong><br>Absent
                    </div>
                    <div class="stat">
                        <strong>{{ $sessionData->count() }}</strong><br>Total
                    </div>
                </div>
                <table class="attendance-table">
                    <thead>
                        <tr><th>Student ID</th><th>Name</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($sessionData as $record)
                            <tr>
                                <td>{{ $record->student_id }}</td>
                                <td>{{ $record->student_name }}</td>
                                <td class="status-{{ $record->status }}">{{ ucfirst($record->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <p>No session data available.</p>
    @endif
</body>
</html>