-- Phase 20 Sample Data: Comprehensive User Activities & Engagement Testing

-- Sample data for user_activities_comprehensive
INSERT INTO user_activities_comprehensive (
    user_id, activity_type, activity_category, activity_name, activity_description,
    carbon_impact_kg, environmental_score, sustainability_points, engagement_score, quality_score,
    base_points, bonus_points, streak_bonus, total_points, device_type, is_verified
) VALUES 
-- Environmental activities
(1, 'waste_report', 'environmental', 'Plastic Bottle Recycling', 'Reported proper recycling of 5 plastic bottles', 0.150, 15, 25, 35.50, 85.0, 20, 5, 3, 28, 'mobile', TRUE),
(1, 'carbon_tracking', 'environmental', 'Daily Carbon Footprint Log', 'Tracked and reduced daily carbon emissions', 2.500, 250, 100, 95.25, 92.0, 50, 15, 8, 73, 'desktop', TRUE),
(2, 'waste_classification', 'environmental', 'AI Waste Sorting', 'Used AI system to classify 10 waste items', 0.075, 8, 15, 28.75, 88.0, 15, 3, 2, 20, 'tablet', TRUE),

-- Social activities  
(1, 'forum_post', 'social', 'Environmental Tips Sharing', 'Shared 5 eco-friendly tips in community forum', 0.000, 5, 10, 42.00, 78.0, 25, 8, 5, 38, 'desktop', TRUE),
(2, 'social_interaction', 'social', 'Green Challenge Participation', 'Joined community green living challenge', 0.200, 20, 30, 38.50, 80.0, 30, 5, 4, 39, 'mobile', TRUE),
(3, 'event_attendance', 'social', 'Sustainability Workshop', 'Attended online sustainability workshop', 0.050, 5, 20, 45.75, 85.0, 35, 10, 6, 51, 'desktop', TRUE),

-- Learning activities
(1, 'quiz_participation', 'learning', 'Climate Change Quiz', 'Completed advanced climate science quiz', 0.000, 0, 15, 32.25, 95.0, 25, 5, 3, 33, 'mobile', TRUE),
(2, 'learning_completion', 'learning', 'Renewable Energy Course', 'Finished online renewable energy course', 0.000, 10, 40, 78.50, 98.0, 60, 15, 9, 84, 'desktop', TRUE),
(3, 'content_view', 'learning', 'Sustainability Articles', 'Read 8 environmental awareness articles', 0.000, 2, 8, 18.75, 75.0, 12, 2, 1, 15, 'tablet', TRUE),

-- Commerce activities
(2, 'product_purchase', 'commerce', 'Eco-friendly Products', 'Purchased sustainable household items', 1.200, 120, 80, 85.00, 90.0, 40, 20, 12, 72, 'mobile', TRUE),
(3, 'product_review', 'commerce', 'Green Product Review', 'Reviewed solar panel efficiency', 0.000, 8, 12, 28.50, 82.0, 18, 4, 2, 24, 'desktop', TRUE),

-- Achievement activities
(1, 'achievement_unlock', 'achievement', 'Eco Warrior Badge', 'Unlocked environmental champion achievement', 0.000, 50, 100, 125.00, 100.0, 100, 25, 15, 140, 'desktop', TRUE),
(2, 'streak_maintain', 'achievement', '30-Day Green Streak', 'Maintained 30-day environmental action streak', 5.000, 500, 200, 175.50, 95.0, 150, 50, 30, 230, 'mobile', TRUE);

-- Sample data for user_streaks_advanced
INSERT INTO user_streaks_advanced (
    user_id, streak_type, streak_category, current_streak, longest_streak, total_activities,
    freeze_cards_remaining, last_activity_date, bonus_points_earned, total_carbon_saved_kg,
    consistency_score, performance_trend
) VALUES 
(1, 'daily_login', 'engagement', 15, 28, 45, 3, CURDATE(), 125, 8.500, 87.5, 'improving'),
(1, 'waste_reporting', 'environmental', 8, 12, 24, 2, CURDATE(), 95, 3.200, 92.0, 'stable'),
(1, 'carbon_tracking', 'environmental', 22, 30, 35, 3, CURDATE(), 210, 15.750, 95.5, 'excellent'),
(2, 'forum_engagement', 'social', 5, 8, 18, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 45, 1.100, 78.0, 'stable'),
(2, 'learning_progress', 'learning', 12, 15, 28, 1, CURDATE(), 180, 0.000, 88.5, 'improving'),
(3, 'daily_activity', 'engagement', 3, 7, 12, 3, CURDATE(), 35, 2.400, 65.0, 'declining');

-- Sample data for user_engagement_scores
INSERT INTO user_engagement_scores (
    user_id, score_period, period_start_date, period_end_date, activity_frequency_score,
    quality_score, consistency_score, environmental_impact_score, overall_engagement_score,
    user_rank_in_period, percentile_rank, churn_risk_score, growth_potential_score
) VALUES 
(1, 'monthly', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 92.5, 88.7, 91.2, 95.8, 167.8, 1, 98.5, 12.5, 88.0),
(2, 'monthly', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 78.3, 85.2, 82.1, 78.9, 124.6, 3, 75.2, 25.8, 72.5),
(3, 'monthly', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 65.8, 80.5, 68.9, 72.3, 98.4, 8, 52.7, 45.2, 58.3),
(1, 'weekly', DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), 95.0, 91.5, 93.8, 97.2, 175.2, 1, 99.1, 8.5, 92.5),
(2, 'weekly', DATE_SUB(CURDATE(), INTERVAL 7 DAY), CURDATE(), 82.5, 87.8, 85.2, 82.1, 132.8, 2, 82.3, 22.1, 76.8);

-- Sample data for user_activity_patterns
INSERT INTO user_activity_patterns (
    user_id, analysis_period, analysis_start_date, analysis_end_date, behavioral_consistency_score,
    primary_activity_times, preferred_activity_types, engagement_decline_indicators,
    churn_risk_indicators, personalization_recommendations
) VALUES 
(1, 'monthly', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 88.5,
 'Peak hours: 8-10 AM, 6-8 PM', 'Environmental tracking, Social engagement, Learning',
 'None detected', 'Low risk indicators', 'Focus on advanced environmental challenges'),
(2, 'monthly', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 75.2,
 'Peak hours: 7-9 PM, Weekend mornings', 'Learning, Commerce, Achievement hunting',
 'Slight decrease in weekend activity', 'Medium risk - engagement volatility', 'Introduce gamification elements'),
(3, 'monthly', DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 62.8,
 'Irregular patterns, mostly evenings', 'Content consumption, Basic activities',
 'Declining engagement frequency', 'High risk - low consistency', 'Simplify interface, add reminders');

-- Sample data for user_habit_tracking
INSERT INTO user_habit_tracking (
    user_id, habit_name, habit_description, habit_category, target_frequency, environmental_focus,
    habit_start_date, current_streak, longest_streak, total_completions, success_rate,
    automaticity_level, has_accountability_partner, habit_status, total_points_earned
) VALUES 
(1, 'Daily Carbon Tracking', 'Track and log daily carbon footprint', 'Environmental Monitoring', 'daily', TRUE,
 DATE_SUB(CURDATE(), INTERVAL 45 DAY), 22, 30, 38, 84.4, 78.5, FALSE, 'active', 1140),
(1, 'Waste Sorting', 'Properly categorize household waste', 'Waste Management', 'daily', TRUE,
 DATE_SUB(CURDATE(), INTERVAL 32 DAY), 8, 12, 28, 87.5, 85.2, TRUE, 'active', 840),
(2, 'Eco Product Research', 'Research sustainable alternatives', 'Sustainable Living', 'weekly', TRUE,
 DATE_SUB(CURDATE(), INTERVAL 21 DAY), 3, 5, 12, 80.0, 65.8, FALSE, 'active', 360),
(2, 'Community Engagement', 'Participate in environmental forums', 'Social Impact', 'daily', FALSE,
 DATE_SUB(CURDATE(), INTERVAL 28 DAY), 5, 8, 18, 64.3, 58.7, FALSE, 'active', 450),
(3, 'Green Learning', 'Complete environmental education content', 'Education', 'weekly', TRUE,
 DATE_SUB(CURDATE(), INTERVAL 14 DAY), 2, 3, 6, 42.9, 35.2, FALSE, 'struggling', 180);

-- Update total_points using the stored procedure logic
UPDATE user_activities_comprehensive SET total_points = base_points + bonus_points + streak_bonus;
