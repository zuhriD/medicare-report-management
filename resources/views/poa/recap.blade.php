<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recap - Plan of Action</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            letter-spacing: 2px;
        }
        
        .summary-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #e8f4f8;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        
        .summary-title {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .summary-stat {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .summary-stat span:first-child {
            font-weight: bold;
            color: #333;
            margin-right: 10px;
            min-width: 120px;
        }
        
        .submitted-count {
            color: #28a745;
            font-weight: bold;
        }
        
        .not-submitted-count {
            color: #dc3545;
            font-weight: bold;
        }
        
        .member-section {
            margin-bottom: 30px;
            padding: 15px;
            border-left: 4px solid #28a745;
            background-color: #f9f9f9;
        }
        
        .member-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .member-name {
            font-weight: bold;
            color: #007bff;
            font-size: 16px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-submitted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-not-submitted {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .poa-item {
            margin: 15px 0 0 0;
            padding: 10px;
            background-color: #fff;
            border-left: 3px solid #28a745;
        }
        
        .poa-module {
            color: #666;
            font-size: 13px;
            margin-bottom: 3px;
        }
        
        .poa-task {
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .poa-subtasks {
            margin-left: 20px;
            color: #555;
        }
        
        .poa-subtasks li {
            list-style: none;
            margin-bottom: 4px;
            font-size: 13px;
        }
        
        .poa-subtasks li:before {
            content: "- ";
            color: #28a745;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .not-submitted-section {
            margin-top: 30px;
            padding: 15px;
            border-left: 4px solid #dc3545;
            background-color: #fff5f5;
        }
        
        .not-submitted-title {
            font-weight: bold;
            color: #dc3545;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .not-submitted-member {
            padding: 8px 12px;
            background-color: white;
            border-left: 3px solid #dc3545;
            margin-bottom: 8px;
            font-size: 13px;
            color: #333;
        }
        
        .copy-button {
            display: block;
            margin: 30px auto;
            padding: 10px 30px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .copy-button:hover {
            background-color: #0056b3;
        }
        
        .copy-message {
            text-align: center;
            color: #28a745;
            font-size: 12px;
            display: none;
            margin-top: 10px;
        }
        
        .recap-section {
            display: none;
        }
        
        .no-data {
            text-align: center;
            color: #999;
            padding: 30px;
            font-style: italic;
        }
        
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .copy-button, .copy-message {
                display: none !important;
            }
            .container {
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">MEDIKCARE - RECAP POA</div>
        
        <!-- Summary Section -->
        <div class="summary-section">
            <div class="summary-title">📊 SUMMARY</div>
            <div class="summary-stat">
                <span>Total Team Members:</span>
                <span class="submitted-count">{{ $allTeamMembers->count() }}</span>
            </div>
            <div class="summary-stat">
                <span>Submitted POA:</span>
                <span class="submitted-count">✅ {{ $submittedCount }}</span>
            </div>
            <div class="summary-stat">
                <span>Not Submitted:</span>
                <span class="not-submitted-count">❌ {{ $notSubmittedMembers->count() }}</span>
            </div>
        </div>
        
        @if ($poas->isEmpty())
            <div class="no-data">No Plan of Action data found</div>
        @else
            @php $counter = 1; @endphp
            @foreach ($poas as $userId => $userPoas)
                @php
                    $userName = $userPoas->first()?->user?->name ?? 'Unknown';
                @endphp
                <div class="member-section">
                    <div class="member-status">
                        <div>
                            <div class="member-name">{{ $counter }}. {{ $userName }} (WFO)</div>
                        </div>
                        <span class="status-badge status-submitted">✅ Submitted</span>
                    </div>
                    @foreach ($userPoas as $poa)
                        <div class="poa-item">
                            <div class="poa-module">Module: {{ $poa->module->name ?? 'N/A' }}</div>
                            <div class="poa-task">{{ $poa->module->name ?? 'N/A' }} | {{ $poa->subModule->name ?? 'N/A' }}</div>
                            <ul class="poa-subtasks">
                                @php
                                    $tasks = is_array($poa->description) 
                                        ? $poa->description 
                                        : array_filter(array_map('trim', explode('-', strip_tags($poa->description ?? ''))));
                                @endphp
                                @forelse ($tasks as $task)
                                    @if (trim(strip_tags($task)))
                                        <li>{{ trim(strip_tags($task)) }}</li>
                                    @endif
                                @empty
                                    <li>No tasks</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>
                @php $counter++; @endphp
            @endforeach
        @endif
        
        <!-- Not Submitted Section -->
        @if ($notSubmittedMembers->count() > 0)
            <div class="not-submitted-section">
                <div class="not-submitted-title">❌ MEMBERS WHO HAVEN'T SUBMITTED</div>
                @foreach ($notSubmittedMembers as $member)
                    <div class="not-submitted-member">{{ $member->name }}</div>
                @endforeach
            </div>
        @endif
    </div>
</body>
</html>
