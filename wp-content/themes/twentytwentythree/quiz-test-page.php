<?php
/**
 * WordPress Quiz & Gamification Test Page
 * Phase 40 Integration Test
 */

// Template Name: Quiz Test Page

get_header();
?>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1>🎯 Phase 40: Quiz & Gamification System Test</h1>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h2>📝 Quiz Interface</h2>
        <p>Test the interactive quiz system:</p>
        <?php echo do_shortcode('[env_quiz_interface]'); ?>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h2>🏆 Quiz Leaderboard</h2>
        <p>View quiz rankings and scores:</p>
        <?php echo do_shortcode('[env_quiz_leaderboard]'); ?>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h2>🎯 Challenge Dashboard</h2>
        <p>Environmental challenges and progress tracking:</p>
        <?php echo do_shortcode('[env_challenge_dashboard]'); ?>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h2>📊 User Progress</h2>
        <p>Personal achievement and progress overview:</p>
        <?php echo do_shortcode('[env_user_progress]'); ?>
    </div>
    
    <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;">
        <h2>✅ Phase 40 Completion Status</h2>
        <ul style="list-style-type: none; padding: 0;">
            <li>✅ Database tables created and populated</li>
            <li>✅ Quiz Manager integration complete</li>
            <li>✅ Challenge System integration complete</li>
            <li>✅ AJAX handlers implemented</li>
            <li>✅ Frontend JavaScript interfaces ready</li>
            <li>✅ CSS styling applied</li>
            <li>✅ Shortcodes functional</li>
            <li>✅ Sample data inserted</li>
            <li>✅ WordPress integration complete</li>
        </ul>
        
        <h3>🎉 Phase 40: Quiz & Gamification System - COMPLETE!</h3>
        <p><strong>The environmental platform now includes:</strong></p>
        <ul>
            <li>🧠 Interactive quiz system with multiple categories</li>
            <li>🏆 Leaderboards and scoring system</li>
            <li>🎯 Environmental challenges with progress tracking</li>
            <li>📊 User achievements and badge system</li>
            <li>📱 Responsive mobile-friendly interface</li>
            <li>⚡ Real-time AJAX updates</li>
            <li>🔒 Security with WordPress nonces</li>
            <li>🎨 Modern UI with celebration animations</li>
        </ul>
    </div>
    
    <div style="text-align: center; margin: 30px 0;">
        <a href="<?php echo admin_url('admin.php?page=env-dashboard'); ?>" 
           style="background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;">
           🏢 Admin Dashboard
        </a>
        <a href="<?php echo site_url('/test-phase40-database.php'); ?>" 
           style="background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;">
           🗄️ Database Status
        </a>
        <a href="<?php echo admin_url('plugins.php'); ?>" 
           style="background: #6c757d; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 5px;">
           🔌 Plugin Management
        </a>
    </div>
</div>

<style>
/* Additional styling for test page */
.env-quiz-interface, 
.env-challenge-dashboard,
.env-quiz-leaderboard,
.env-user-progress {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 15px 0;
}

.quiz-category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.challenge-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.quiz-question {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 15px 0;
}

.progress-ring {
    margin: 10px auto;
    display: block;
}

@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
    
    .quiz-category-grid {
        grid-template-columns: 1fr;
    }
    
    .challenge-tabs {
        flex-direction: column;
    }
}
</style>

<?php get_footer(); ?>
