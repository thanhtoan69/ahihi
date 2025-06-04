<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signature Confirmation</title>
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
        }
        .petition-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2E8B57;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #2E8B57, #228B22);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            opacity: 0.9;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .progress-bar {
            background-color: #e0e0e0;
            border-radius: 10px;
            padding: 3px;
            margin: 10px 0;
        }
        .progress-fill {
            background: linear-gradient(135deg, #2E8B57, #228B22);
            height: 20px;
            border-radius: 8px;
            width: {{progress_percentage}}%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌱 Thank You for Your Signature!</h1>
        </div>
        
        <div class="content">
            <p>Dear {{user_name}},</p>
            
            <p>Thank you for signing our petition! Your voice matters and together we can make a difference for our environment.</p>
            
            <div class="petition-info">
                <h3>{{petition_title}}</h3>
                <p>{{petition_excerpt}}</p>
                
                <div class="progress-bar">
                    <div class="progress-fill">
                        {{current_signatures}} / {{goal_signatures}}
                    </div>
                </div>
                <p><strong>Progress:</strong> {{current_signatures}} signatures of {{goal_signatures}} goal ({{progress_percentage}}%)</p>
            </div>
            
            <?php if (isset($verification_required) && $verification_required): ?>
            <div style="background-color: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;">
                <h4>⚠️ Verification Required</h4>
                <p>To complete your signature, please verify your email address by clicking the button below:</p>
                <a href="{{verification_url}}" class="button">Verify Your Email</a>
                <p><small>This link will expire in 24 hours.</small></p>
            </div>
            <?php endif; ?>
            
            <h3>Help Us Reach Our Goal!</h3>
            <p>Share this petition with your friends and family to amplify our impact:</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{share_facebook}}" style="display: inline-block; margin: 5px; padding: 10px 15px; background-color: #3b5998; color: white; text-decoration: none; border-radius: 5px;">Facebook</a>
                <a href="{{share_twitter}}" style="display: inline-block; margin: 5px; padding: 10px 15px; background-color: #1da1f2; color: white; text-decoration: none; border-radius: 5px;">Twitter</a>
                <a href="{{share_whatsapp}}" style="display: inline-block; margin: 5px; padding: 10px 15px; background-color: #25d366; color: white; text-decoration: none; border-radius: 5px;">WhatsApp</a>
            </div>
            
            <p>You can also view the full petition and track its progress at: <a href="{{petition_url}}">{{petition_url}}</a></p>
            
            <p>Thank you for being part of the solution!</p>
            
            <p>Best regards,<br>
            <strong>{{site_name}} Team</strong></p>
        </div>
        
        <div class="footer">
            <p>You're receiving this email because you signed a petition on {{site_name}}.</p>
            <p><a href="{{unsubscribe_url}}">Unsubscribe</a> | <a href="{{site_url}}">Visit our website</a></p>
        </div>
    </div>
</body>
</html>
