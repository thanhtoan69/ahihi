<?php
/**
 * Promotional Email Template
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
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        /* Container */
        .promo-container {
            background-color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(46, 125, 50, 0.15);
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #4caf50 100%);
            color: white;
            padding: 50px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .hero-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            display: inline-block;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        
        .hero-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.2;
            position: relative;
            z-index: 2;
        }
        
        .hero-subtitle {
            font-size: 18px;
            opacity: 0.95;
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
        }
        
        .hero-cta {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8f00 100%);
            color: white !important;
            text-decoration: none;
            padding: 18px 40px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 18px;
            display: inline-block;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
            box-shadow: 0 4px 15px rgba(255, 111, 0, 0.3);
        }
        
        .hero-cta:hover {
            background: linear-gradient(135deg, #e65100 0%, #ff6f00 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 111, 0, 0.4);
        }
        
        /* Offer Section */
        .offer-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8fff8 100%);
            padding: 50px 30px;
            text-align: center;
            border-bottom: 3px solid #4caf50;
        }
        
        .offer-title {
            font-size: 28px;
            color: #2e7d32;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .offer-description {
            font-size: 16px;
            color: #424242;
            line-height: 1.6;
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .discount-box {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        
        .discount-box::before {
            content: '';
            position: absolute;
            top: -10px;
            right: -10px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .discount-percentage {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }
        
        .discount-text {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .discount-code {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            border: 2px dashed rgba(255, 255, 255, 0.5);
        }
        
        /* Features Section */
        .features-section {
            padding: 50px 30px;
            background-color: #ffffff;
        }
        
        .features-title {
            font-size: 24px;
            color: #2e7d32;
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .features-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
        }
        
        .feature-item {
            flex: 1;
            min-width: 250px;
            text-align: center;
            padding: 20px;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 20px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .feature-title {
            font-size: 18px;
            color: #2e7d32;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .feature-description {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        /* Urgency Section */
        .urgency-section {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            padding: 40px 30px;
            text-align: center;
            border-left: 5px solid #ff9800;
        }
        
        .urgency-title {
            font-size: 22px;
            color: #e65100;
            font-weight: bold;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .urgency-icon {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }
        
        .countdown-item {
            background-color: #e65100;
            color: white;
            padding: 15px;
            border-radius: 8px;
            min-width: 60px;
        }
        
        .countdown-number {
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        
        .countdown-label {
            font-size: 12px;
            text-transform: uppercase;
        }
        
        /* Social Proof */
        .social-proof {
            background-color: #f8fff8;
            padding: 40px 30px;
            text-align: center;
        }
        
        .testimonial-box {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #4caf50;
        }
        
        .testimonial-text {
            font-size: 16px;
            font-style: italic;
            color: #424242;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .testimonial-author {
            font-weight: bold;
            color: #2e7d32;
        }
        
        .customer-count {
            font-size: 18px;
            color: #666;
            margin-top: 20px;
        }
        
        .customer-number {
            font-size: 24px;
            font-weight: bold;
            color: #4caf50;
        }
        
        /* Final CTA */
        .final-cta {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 50px 30px;
            text-align: center;
        }
        
        .final-cta-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .final-cta-subtitle {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .final-cta-button {
            background: linear-gradient(135deg, #ff6f00 0%, #ff8f00 100%);
            color: white !important;
            text-decoration: none;
            padding: 20px 50px;
            border-radius: 35px;
            font-weight: bold;
            font-size: 20px;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(255, 111, 0, 0.3);
        }
        
        .final-cta-button:hover {
            background: linear-gradient(135deg, #e65100 0%, #ff6f00 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 111, 0, 0.4);
        }
        
        /* Footer */
        .promo-footer {
            background-color: #f5f5f5;
            padding: 30px;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        
        .footer-links {
            margin-bottom: 15px;
        }
        
        .footer-link {
            color: #4caf50;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .promo-container {
                width: 100% !important;
                border-radius: 0;
            }
            
            .hero-section,
            .offer-section,
            .features-section,
            .urgency-section,
            .social-proof,
            .final-cta {
                padding: 30px 20px;
            }
            
            .hero-title {
                font-size: 28px;
            }
            
            .hero-subtitle {
                font-size: 16px;
            }
            
            .features-grid {
                flex-direction: column;
            }
            
            .countdown-timer {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .final-cta-title {
                font-size: 24px;
            }
            
            .final-cta-button {
                padding: 15px 30px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div style="padding: 20px 0;">
        <div class="promo-container">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-badge">{{promo_badge}}</div>
                <h1 class="hero-title">{{campaign_title}}</h1>
                <p class="hero-subtitle">{{hero_subtitle}}</p>
                <a href="{{cta_url}}{{tracking_params}}" class="hero-cta">{{hero_cta_text}}</a>
            </div>
            
            <!-- Offer Section -->
            <div class="offer-section">
                <h2 class="offer-title">{{offer_title}}</h2>
                <p class="offer-description">{{offer_description}}</p>
                
                {{#if discount}}
                <div class="discount-box">
                    <span class="discount-percentage">{{discount.percentage}}% OFF</span>
                    <div class="discount-text">{{discount.description}}</div>
                    {{#if discount.code}}
                    <div class="discount-code">Use Code: {{discount.code}}</div>
                    {{/if}}
                </div>
                {{/if}}
            </div>
            
            <!-- Features Section -->
            {{#if features}}
            <div class="features-section">
                <h3 class="features-title">Why Choose Our Eco-Friendly Solutions?</h3>
                <div class="features-grid">
                    {{#each features}}
                    <div class="feature-item">
                        <div class="feature-icon">{{icon}}</div>
                        <h4 class="feature-title">{{title}}</h4>
                        <p class="feature-description">{{description}}</p>
                    </div>
                    {{/each}}
                </div>
            </div>
            {{/if}}
            
            <!-- Urgency Section -->
            {{#if urgency}}
            <div class="urgency-section">
                <h3 class="urgency-title">
                    <span class="urgency-icon">⏰</span>
                    {{urgency.title}}
                </h3>
                {{#if urgency.countdown}}
                <div class="countdown-timer">
                    <div class="countdown-item">
                        <span class="countdown-number">{{urgency.countdown.days}}</span>
                        <span class="countdown-label">Days</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number">{{urgency.countdown.hours}}</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number">{{urgency.countdown.minutes}}</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                </div>
                {{/if}}
                <p>{{urgency.description}}</p>
            </div>
            {{/if}}
            
            <!-- Social Proof -->
            {{#if testimonial}}
            <div class="social-proof">
                <div class="testimonial-box">
                    <p class="testimonial-text">"{{testimonial.text}}"</p>
                    <div class="testimonial-author">- {{testimonial.author}}</div>
                </div>
                {{#if customer_count}}
                <div class="customer-count">
                    Join <span class="customer-number">{{customer_count}}</span> satisfied eco-warriors!
                </div>
                {{/if}}
            </div>
            {{/if}}
            
            <!-- Final CTA -->
            <div class="final-cta">
                <h2 class="final-cta-title">Ready to Make a Difference?</h2>
                <p class="final-cta-subtitle">{{final_cta_subtitle}}</p>
                <a href="{{cta_url}}{{tracking_params}}" class="final-cta-button">{{final_cta_text}}</a>
                <p style="margin-top: 20px; font-size: 14px; opacity: 0.8;">
                    {{guarantee_text}}
                </p>
            </div>
            
            <!-- Footer -->
            <div class="promo-footer">
                <div class="footer-links">
                    <a href="{{site_url}}" class="footer-link">Website</a>
                    <a href="{{terms_url}}" class="footer-link">Terms</a>
                    <a href="{{privacy_url}}" class="footer-link">Privacy</a>
                    <a href="{{contact_url}}" class="footer-link">Contact</a>
                </div>
                
                <p>{{site_name}} - {{site_address}}</p>
                <p style="margin-top: 10px;">
                    <a href="{{preferences_url}}{{tracking_params}}" style="color: #4caf50;">Update Preferences</a> | 
                    <a href="{{unsubscribe_url}}{{tracking_params}}" style="color: #999;">Unsubscribe</a>
                </p>
                <p style="margin-top: 10px;">
                    This promotional email was sent to {{subscriber_email}}.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Tracking Pixel -->
    <img src="{{tracking_pixel_url}}" width="1" height="1" style="display: none;" alt="">
</body>
</html>
