<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Events</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111111;
            background-color: #ffffff;
            padding: 40px;
        }

        .title {
            text-align: center;
            font-size: 18px;
            color: #111111;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        thead {
            background-color: #f0f0f0;
        }

        th {
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            border-bottom: 2px solid #d1d1d1;
            color: #000000;
        }

        td {
            padding: 12px 10px;
            font-size: 11px;
            border-bottom: 1px solid #e0e0e0;
            color: #222222;
            vertical-align: top;
        }

        tr:nth-child(even) {
            background-color: #fafafa;
        }

        .reference {
            color: #555555;
            font-style: italic;
        }

        .no-data {
            text-align: center;
            margin-top: 60px;
            font-style: italic;
            color: #888888;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="title">Eventos registrado del empleado</div>

@if(!empty($events) && count($events) > 0)
    <table>
        <thead>
        <tr>
            <th>Event</th>
            <th>Section</th>
            <th>Reference</th>
            <th>Message</th>
            <th>Date</th>
            <th>Employee</th>
        </tr>
        </thead>
        <tbody>
        @foreach($events as $event)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $event->event)) }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $event->section)) }}</td>
                <td>
                    @if($event->referenceTable && $event->referenceId)
                        <span class="reference">{{ $event->referenceTable }} #{{ $event->referenceId }}</span>
                    @else
                        <span class="reference">-</span>
                    @endif
                </td>
                <td>{{ $event->message ?? '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($event->createdAt)->format('Y-m-d H:i') }}</td>
                <td>{{ $event->employeeName }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="no-data">No activity data available.</div>
@endif

</body>
</html>
