<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milestone Achieved!</title>
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
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #333;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .celebration {
            text-align: center;
            font-size: 48px;
            margin: 20px 0;
            animation: bounce 1s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        .milestone-card {
            background: linear-gradient(135deg, #2E8B57, #228B22);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
        }
        .milestone-number {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .progress-bar {
            background-color: #e0e0e0;
            border-radius: 10px;
            padding: 3px;
            margin: 20px 0;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #2E8B57;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2E8B57;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #2E8B57, #228B22);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="celebration">🎉</div>
            <h1>Milestone Achieved!</h1>
        </div>
        
        <div style="padding: 20px 0;">
            <p>Dear {{user_name}},</p>
            
            <p>Amazing news! The petition you signed has reached an important milestone!</p>
            
            <div class="milestone-card">
                <div class="milestone-number">{{milestone_signatures}}</div>
                <h3>Signatures Collected!</h3>
                <p><strong>{{petition_title}}</strong></p>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill">
                    {{current_signatures}} / {{goal_signatures}}
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{progress_percentage}}%</div>
                    <div>Progress</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{days_active}}</div>
                    <div>Days Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{total_shares}}</div>
                    <div>Total Shares</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{signatures_today}}</div>
                    <div>Today's Signatures</div>
                </div>
            </div>
            
            <h3>🌱 Your Impact</h3>
            <p>Thanks to supporters like you, we're making real progress on this important environmental issue. Every signature brings us closer to creating meaningful change.</p>
            
            <p><strong>What happens next?</strong></p>
            <ul>
                <li>We'll continue collecting signatures toward our {{goal_signatures}} goal</li>
                <li>Updates will be shared with all supporters</li>
                <li>We'll submit the petition to relevant authorities</li>
                <li>You'll be notified of any major developments</li>
            </ul>
            
            <h3>Help Us Reach Even More People!</h3>
            <p>Share this petition with your network to maximize our impact:</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{share_facebook}}" class="button">📘 Facebook</a>
                <a href="{{share_twitter}}" class="button">🐦 Twitter</a>
                <a href="{{share_whatsapp}}" class="button">💬 WhatsApp</a>
                <a href="{{share_email}}" class="button">📧 Email</a>
            </div>
            
            <p>View the full petition: <a href="{{petition_url}}">{{petition_url}}</a></p>
            
            <p>Thank you for being part of this movement for environmental change!</p>
            
            <p>Together, we're stronger! 🌍</p>
            
            <p>Best regards,<br>
            <strong>{{site_name}} Team</strong></p>
        </div>
        
        <div class="footer">
            <p>You're receiving this email because you signed the petition "{{petition_title}}"</p>
            <p><a href="{{unsubscribe_url}}">Unsubscribe</a> | <a href="{{site_url}}">Visit our website</a></p>
        </div>
    </div>
</body>
</html>
