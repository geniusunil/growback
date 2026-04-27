<!DOCTYPE html>
<html>
<head>
    <title>Task Reminder</title>
</head>
<body>
    <h1>Task Reminder</h1>
    <p>Hello,</p>
    <p>This is a reminder for your scheduled task:</p>
    <p><strong>{{ $taskDetails['task_description'] }}</strong></p>
    <p>Scheduled Time: {{ $taskDetails['scheduled_time'] }}</p>
    <p>Thank you!</p>
</body>
</html>
