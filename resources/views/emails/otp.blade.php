<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .otp-box {
            background-color: #ffffff;
            border: 2px solid #4CAF50; /* Green border */
            border-radius: 12px;
            padding: 35px 50px;
            text-align: center;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            max-width: 400px;
        }

        .otp-title {
            font-size: 20px;
            color: #333333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .otp-message {
            font-size: 14px;
            color: #555555;
            margin-bottom: 20px;
        }

        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #4CAF50;
            letter-spacing: 6px;
            margin-bottom: 15px;
        }

        .otp-footer {
            font-size: 12px;
            color: #777777;
        }
    </style>
</head>
<body>
    <div class="otp-box">
        <div class="otp-title">Almost there!</div>
        <div class="otp-message">Use the code below to securely verify your account:</div>
        <div class="otp-code">{{ $otp }}</div>
        <div class="otp-footer">This code will expire in 5 minutes.</div>
    </div>
</body>
</html>