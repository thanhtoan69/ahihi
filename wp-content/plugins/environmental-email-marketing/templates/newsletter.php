<?php
/**
 * Newsletter Email Template
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f0f8f0;
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        /* Container */
        .newsletter-container {
            background-color: #ffffff;
            max-width: 650px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .newsletter-header {
            background: linear-gradient(45deg, #1b5e20 0%, #2e7d32 50%, #4caf50 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .newsletter-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="leaves" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23leaves)"/></svg>');
            opacity: 0.3;
        }
        
        .newsletter-logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }
        
        .newsletter-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .newsletter-date {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        /* Featured Story */
        .featured-story {
            padding: 40px 30px;
            background: linear-gradient(135deg, #e8f5e8 0%, #ffffff 100%);
            border-bottom: 3px solid #4caf50;
        }
        
        .featured-label {
            background-color: #4caf50;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .featured-title {
            font-size: 24px;
            color: #1b5e20;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .featured-excerpt {
            font-size: 16px;
            color: #424242;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .featured-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .read-more-btn {
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .read-more-btn:hover {
            background: linear-gradient(135deg, #388e3c 0%, #4caf50 100%);
            transform: translateY(-2px);
        }
        
        /* Content Sections */
        .content-section {
            padding: 40px 30px;
            border-bottom: 1px solid #e8f5e8;
        }
        
        .section-title {
            font-size: 22px;
            color: #2e7d32;
            font-weight: bold;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }
        
        .section-icon {
            width: 28px;
            height: 28px;
            margin-right: 12px;
        }
        
        /* Article Grid */
        .articles-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .article-card {
            flex: 1;
            min-width: 250px;
            background-color: #f8f8f8;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #4caf50;
        }
        
        .article-title {
            font-size: 16px;
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .article-excerpt {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }
        
        .article-link {
            color: #4caf50;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }
        
        .article-link:hover {
            text-decoration: underline;
        }
        
        /* Environmental Stats */
        .stats-section {
            background: linear-gradient(135deg, #e8f5e8 0%, #f1f8e9 100%);
            padding: 40px 30px;
            text-align: center;
        }
        
        .stats-title {
            font-size: 24px;
            color: #2e7d32;
            font-weight: bold;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            text-align: center;
            flex: 1;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 15px;
            background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #2e7d32;
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Call to Action */
        .cta-section {
            background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .cta-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .cta-description {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .cta-button {
            background-color: white;
            color: #2e7d32 !important;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        /* Footer */
        .newsletter-footer {
            background-color: #f5f5f5;
            padding: 40px 30px;
            text-align: center;
            color: #666666;
            font-size: 14px;
        }
        
        .footer-links {
            margin-bottom: 20px;
        }
        
        .footer-link {
            color: #4caf50;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 500;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        .social-section {
            margin: 30px 0;
            padding: 20px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .social-title {
            font-size: 16px;
            color: #2e7d32;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .social-link {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #4caf50;
            text-align: center;
            line-height: 40px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background-color: #388e3c;
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media only screen and (max-width: 600px) {
            .newsletter-container {
                width: 100% !important;
                border-radius: 0;
            }
            
            .content-section,
            .featured-story,
            .stats-section,
            .cta-section {
                padding: 30px 20px;
            }
            
            .newsletter-title {
                font-size: 24px;
            }
            
            .featured-title {
                font-size: 20px;
            }
            
            .articles-grid {
                flex-direction: column;
            }
            
            .stats-grid {
                flex-direction: column;
                gap: 30px;
            }
            
            .social-links {
                flex-wrap: wrap;
            }
            
            .cta-title {
                font-size: 24px;
            }
            
            .cta-description {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div style="padding: 20px 0;">
        <div class="newsletter-container">
            <!-- Header -->
            <div class="newsletter-header">
                <img src="{{site_logo}}" alt="{{site_name}}" class="newsletter-logo">
                <div class="newsletter-title">{{campaign_title}}</div>
                <div class="newsletter-date">{{newsletter_date}}</div>
            </div>
            
            <!-- Featured Story -->
            {{#if featured_story}}
            <div class="featured-story">
                <div class="featured-label">Featured Story</div>
                <h2 class="featured-title">{{featured_story.title}}</h2>
                {{#if featured_story.image}}
                <img src="{{featured_story.image}}" alt="{{featured_story.title}}" class="featured-image">
                {{/if}}
                <p class="featured-excerpt">{{featured_story.excerpt}}</p>
                <a href="{{featured_story.url}}{{tracking_params}}" class="read-more-btn">Read Full Story</a>
            </div>
            {{/if}}
            
            <!-- Latest News -->
            <div class="content-section">
                <h3 class="section-title">
                    <span class="section-icon">📰</span>
                    Latest Environmental News
                </h3>
                <div class="articles-grid">
                    {{#each news_articles}}
                    <div class="article-card">
                        <h4 class="article-title">{{title}}</h4>
                        <p class="article-excerpt">{{excerpt}}</p>
                        <a href="{{url}}{{../tracking_params}}" class="article-link">Read More →</a>
                    </div>
                    {{/each}}
                </div>
            </div>
            
            <!-- Eco Tips -->
            <div class="content-section">
                <h3 class="section-title">
                    <span class="section-icon">💡</span>
                    Eco-Friendly Tips
                </h3>
                <div class="articles-grid">
                    {{#each eco_tips}}
                    <div class="article-card">
                        <h4 class="article-title">{{title}}</h4>
                        <p class="article-excerpt">{{description}}</p>
                        <a href="{{url}}{{../tracking_params}}" class="article-link">Learn More →</a>
                    </div>
                    {{/each}}
                </div>
            </div>
            
            <!-- Environmental Stats -->
            <div class="stats-section">
                <h3 class="stats-title">Our Community Impact This Month</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">🌱</div>
                        <span class="stat-number">{{community_stats.trees_planted}}</span>
                        <span class="stat-label">Trees Planted</span>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">♻️</div>
                        <span class="stat-number">{{community_stats.co2_reduced}}kg</span>
                        <span class="stat-label">CO₂ Reduced</span>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon">🌍</div>
                        <span class="stat-number">{{community_stats.actions_taken}}</span>
                        <span class="stat-label">Eco Actions</span>
                    </div>
                </div>
                <p style="font-size: 16px; margin-top: 20px;">
                    Together, we're making a real difference for our planet!
                </p>
            </div>
            
            <!-- Call to Action -->
            <div class="cta-section">
                <h3 class="cta-title">Join Our Next Green Initiative</h3>
                <p class="cta-description">
                    Be part of something bigger. Take action for the environment today.
                </p>
                <a href="{{cta_url}}{{tracking_params}}" class="cta-button">{{cta_text}}</a>
            </div>
            
            <!-- Footer -->
            <div class="newsletter-footer">
                <div class="footer-links">
                    <a href="{{site_url}}" class="footer-link">Visit Website</a>
                    <a href="{{blog_url}}" class="footer-link">Blog</a>
                    <a href="{{events_url}}" class="footer-link">Events</a>
                    <a href="{{resources_url}}" class="footer-link">Resources</a>
                </div>
                
                <div class="social-section">
                    <div class="social-title">Follow Us for Daily Eco-Tips</div>
                    <div class="social-links">
                        {{#if facebook_url}}
                        <a href="{{facebook_url}}" class="social-link">f</a>
                        {{/if}}
                        {{#if twitter_url}}
                        <a href="{{twitter_url}}" class="social-link">t</a>
                        {{/if}}
                        {{#if instagram_url}}
                        <a href="{{instagram_url}}" class="social-link">i</a>
                        {{/if}}
                        {{#if linkedin_url}}
                        <a href="{{linkedin_url}}" class="social-link">in</a>
                        {{/if}}
                    </div>
                </div>
                
                <p>{{site_name}} - {{site_address}}</p>
                <p style="margin-top: 15px;">
                    <a href="{{preferences_url}}{{tracking_params}}" style="color: #4caf50;">Update Preferences</a> | 
                    <a href="{{unsubscribe_url}}{{tracking_params}}" style="color: #999;">Unsubscribe</a>
                </p>
                <p style="margin-top: 15px; font-size: 11px; color: #999;">
                    You received this newsletter because you subscribed to {{site_name}}. 
                    This email was sent to {{subscriber_email}}.
                </p>
            </div>
        </div>
    </div>
    
    <!-- Tracking Pixel -->
    <img src="{{tracking_pixel_url}}" width="1" height="1" style="display: none;" alt="">
</body>
</html>
