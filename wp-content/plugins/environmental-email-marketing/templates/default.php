<?php
/**
 * Default Email Template
 * 
 * @package Environmental_Email_Marketing
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo get_locale(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{campaign_title}}</title>
    <style>
        /* Reset styles */
        body, table, td, p, h1, h2, h3, h4, h5, h6 {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        /* Container */
        .email-container {
            background-color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .email-header {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
        }
        
        .header-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* Content */
        .email-content {
            padding: 40px 30px;
            line-height: 1.6;
            color: #333333;
        }
        
        .content-block {
            margin-bottom: 30px;
        }
        
        .greeting {
            font-size: 18px;
            color: #2e7d32;
            margin-bottom: 20px;
        }
        
        .main-content {
            font-size: 16px;
            margin-bottom: 25px;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white !important;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            background: linear-gradient(135deg, #388e3c 0%, #4caf50 100%);
            transform: translateY(-2px);
        }
        
        /* Environmental Impact Section */
        .impact-section {
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
            border-left: 4px solid #4caf50;
        }
        
        .impact-title {
            color: #2e7d32;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .impact-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        
        .impact-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        
        .impact-stat {
            text-align: center;
            flex: 1;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2e7d32;
            display: block;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        /* Footer */
        .email-footer {
            background-color: #f8f8f8;
            padding: 30px 20px;
            text-align: center;
            color: #666666;
            font-size: 14px;
            border-top: 1px solid #e0e0e0;
        }
        
        .social-links {
            margin: 20px 0;
        }
        
        .social-link {
            display: inline-block;
            margin: 0 10px;
            text-decoration: none;
        }
        
        .social-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #4caf50;
        }
        
        .unsubscribe-link {
            color: #999999;
            text-decoration: none;
            font-size: 12px;
        }
        
        .unsubscribe-link:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                border-radius: 0;
            }
            
            .email-content {
                padding: 30px 20px;
            }
            
            .header-title {
                font-size: 20px;
            }
            
            .impact-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .cta-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div style="padding: 20px 0;">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <img src="{{site_logo}}" alt="{{site_name}}" class="logo">
                <div class="header-title">{{campaign_title}}</div>
                <div class="header-subtitle">Making a difference together</div>
            </div>
            
            <!-- Content -->
            <div class="email-content">
                <div class="greeting">
                    Hello {{subscriber_name}},
                </div>
                
                <div class="main-content">
                    {{email_content}}
                </div>
                
                {{#if cta_text}}
                <div style="text-align: center;">
                    <a href="{{cta_url}}{{tracking_params}}" class="cta-button">{{cta_text}}</a>
                </div>
                {{/if}}
                
                {{#if show_impact}}
                <div class="impact-section">
                    <div class="impact-title">
                        <span class="impact-icon">🌱</span>
                        Your Environmental Impact
                    </div>
                    <p>Here's how you're making a difference:</p>
                    <div class="impact-stats">
                        <div class="impact-stat">
                            <span class="stat-number">{{co2_saved}}kg</span>
                            <span class="stat-label">CO₂ Saved</span>
                        </div>
                        <div class="impact-stat">
                            <span class="stat-number">{{trees_planted}}</span>
                            <span class="stat-label">Trees Planted</span>
                        </div>
                        <div class="impact-stat">
                            <span class="stat-number">{{actions_taken}}</span>
                            <span class="stat-label">Eco Actions</span>
                        </div>
                    </div>
                </div>
                {{/if}}
            </div>
            
            <!-- Footer -->
            <div class="email-footer">
                <div class="social-links">
                    {{#if facebook_url}}
                    <a href="{{facebook_url}}" class="social-link">
                        <img src="{{template_url}}/assets/images/facebook.png" alt="Facebook" class="social-icon">
                    </a>
                    {{/if}}
                    {{#if twitter_url}}
                    <a href="{{twitter_url}}" class="social-link">
                        <img src="{{template_url}}/assets/images/twitter.png" alt="Twitter" class="social-icon">
                    </a>
                    {{/if}}
                    {{#if instagram_url}}
                    <a href="{{instagram_url}}" class="social-link">
                        <img src="{{template_url}}/assets/images/instagram.png" alt="Instagram" class="social-icon">
                    </a>
                    {{/if}}
                </div>
                
                <p>{{site_name}} - {{site_address}}</p>
                <p>
                    <a href="{{preferences_url}}{{tracking_params}}" style="color: #4caf50;">Update Preferences</a> | 
                    <a href="{{unsubscribe_url}}{{tracking_params}}" class="unsubscribe-link">Unsubscribe</a>
                </p>
                <p style="margin-top: 15px; font-size: 11px; color: #999;">
                    This email was sent to {{subscriber_email}}. 
                    If you no longer wish to receive these emails, you can unsubscribe at any time.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Tracking Pixel -->
    <img src="{{tracking_pixel_url}}" width="1" height="1" style="display: none;" alt="">
</body>
</html>
