<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #2E8B57, #228B22);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px 0;
            text-align: center;
        }
        .verification-box {
            background-color: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px solid #2E8B57;
        }
        .verification-code {
            font-size: 32px;
            font-weight: bold;
            color: #2E8B57;
            letter-spacing: 4px;
            margin: 20px 0;
            padding: 15px;
            background-color: #e8f5e8;
            border-radius: 8px;
            border: 2px dashed #2E8B57;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #2E8B57, #228B22);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">🔒</div>
            <h1>Verify Your Email Address</h1>
        </div>
        
        <div class="content">
            <p>Hello {{user_name}},</p>
            
            <p>Thank you for signing our petition: <strong>{{petition_title}}</strong></p>
            
            <p>To complete your signature and help us reach our goal, please verify your email address.</p>
            
            <div class="verification-box">
                <h3>Click the button below to verify:</h3>
                <a href="{{verification_url}}" class="button">✅ Verify My Email</a>
                
                <div style="margin: 30px 0;">
                    <p><strong>Or use this verification code:</strong></p>
                    <div class="verification-code">{{verification_code}}</div>
                    <p><small>Enter this code at: <a href="{{manual_verification_url}}">{{manual_verification_url}}</a></small></p>
                </div>
            </div>
            
            <div class="warning">
                <strong>⏰ Time Sensitive:</strong> This verification link will expire in 24 hours for security reasons.
            </div>
            
            <p>Once verified, your signature will be counted and you'll receive updates about the petition's progress.</p>
            
            <p><strong>Why do we need verification?</strong></p>
            <ul style="text-align: left; display: inline-block;">
                <li>Ensure authentic signatures</li>
                <li>Prevent spam and duplicate signatures</li>
                <li>Maintain petition integrity</li>
                <li>Send you important updates</li>
            </ul>
            
            <p>If you didn't sign this petition, you can safely ignore this email.</p>
            
            <p>Thank you for supporting environmental causes!</p>
            
            <p>Best regards,<br>
            <strong>{{site_name}} Team</strong></p>
        </div>
        
        <div class="footer">
            <p>You're receiving this email because someone signed a petition using your email address.</p>
            <p>If this wasn't you, please ignore this email.</p>
            <p><a href="{{site_url}}">Visit our website</a> | <a href="mailto:{{support_email}}">Contact Support</a></p>
        </div>
    </div>
</body>
</html>
