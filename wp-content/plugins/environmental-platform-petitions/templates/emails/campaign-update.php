<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Update</title>
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
        .update-badge {
            background-color: #17a2b8;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        .content-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2E8B57;
        }
        .progress-section {
            text-align: center;
            margin: 30px 0;
        }
        .progress-bar {
            background-color: #e0e0e0;
            border-radius: 10px;
            padding: 3px;
            margin: 15px 0;
        }
        .progress-fill {
            background: linear-gradient(135deg, #2E8B57, #228B22);
            height: 25px;
            border-radius: 8px;
            width: {{progress_percentage}}%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .stats-row {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
            text-align: center;
        }
        .stat-item {
            flex: 1;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2E8B57;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #2E8B57, #228B22);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 5px;
        }
        .secondary-button {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        .quote {
            font-style: italic;
            font-size: 16px;
            color: #555;
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background-color: #f1f8ff;
            border-radius: 8px;
            border-left: 4px solid #0366d6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="update-badge">CAMPAIGN UPDATE</div>
            <h1>📢 Important Update</h1>
        </div>
        
        <div style="padding: 20px 0;">
            <p>Dear {{user_name}},</p>
            
            <p>We have an important update about the petition you signed: <strong>{{petition_title}}</strong></p>
            
            <div class="content-section">
                <h3>{{update_title}}</h3>
                <div style="color: #666; font-size: 12px; margin-bottom: 15px;">
                    📅 {{update_date}} by {{update_author}}
                </div>
                <div>{{update_content}}</div>
            </div>
            
            <div class="progress-section">
                <h3>Current Progress</h3>
                <div class="progress-bar">
                    <div class="progress-fill">
                        {{current_signatures}} / {{goal_signatures}}
                    </div>
                </div>
                
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-number">{{current_signatures}}</div>
                        <div class="stat-label">Total Signatures</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{progress_percentage}}%</div>
                        <div class="stat-label">Progress</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{signatures_this_week}}</div>
                        <div class="stat-label">This Week</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">{{days_remaining}}</div>
                        <div class="stat-label">Days Left</div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($call_to_action) && !empty($call_to_action)): ?>
            <div class="highlight">
                <h4>🚨 Action Needed</h4>
                <p>{{call_to_action}}</p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($quote) && !empty($quote)): ?>
            <div class="quote">
                "{{quote}}"
                <?php if (isset($quote_author)): ?>
                <br><strong>- {{quote_author}}</strong>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <h3>🌱 How You Can Help</h3>
            <ul>
                <li><strong>Share:</strong> Spread the word to your friends and family</li>
                <li><strong>Engage:</strong> Comment and discuss on social media</li>
                <li><strong>Stay Informed:</strong> Keep up with campaign updates</li>
                <li><strong>Take Action:</strong> Participate in related environmental activities</li>
            </ul>
            
            <div class="action-buttons">
                <a href="{{petition_url}}" class="button">📋 View Petition</a>
                <a href="{{share_url}}" class="button">📤 Share Now</a>
                <a href="{{campaign_url}}" class="button secondary-button">📊 Campaign Details</a>
            </div>
            
            <p>Thank you for your continued support in making a difference for our environment!</p>
            
            <p>Together for a greener future! 🌍</p>
            
            <p>Best regards,<br>
            <strong>{{site_name}} Team</strong></p>
        </div>
        
        <div class="footer">
            <p>You're receiving this email because you signed the petition "{{petition_title}}"</p>
            <p><a href="{{unsubscribe_url}}">Unsubscribe from updates</a> | <a href="{{preferences_url}}">Email Preferences</a> | <a href="{{site_url}}">Visit Website</a></p>
        </div>
    </div>
</body>
</html>
