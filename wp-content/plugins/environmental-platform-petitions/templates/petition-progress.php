<?php
/**
 * Petition Progress Tracking Template
 * 
 * @package Environmental_Platform_Petitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get petition data
$petition_id = isset($petition_id) ? $petition_id : get_the_ID();
$goal = get_post_meta($petition_id, 'petition_goal', true) ?: 1000;
$current_signatures = Environmental_Platform_Petitions_Database::get_signature_count($petition_id);
$progress_percentage = min(100, ($current_signatures / $goal) * 100);

// Get additional metrics
$today_signatures = Environmental_Platform_Petitions_Database::get_signatures_count_by_period($petition_id, 'today');
$week_signatures = Environmental_Platform_Petitions_Database::get_signatures_count_by_period($petition_id, 'week');
$month_signatures = Environmental_Platform_Petitions_Database::get_signatures_count_by_period($petition_id, 'month');

// Get deadline info
$deadline = get_post_meta($petition_id, 'petition_deadline', true);
$days_remaining = 0;
if ($deadline) {
    $days_remaining = max(0, ceil((strtotime($deadline) - time()) / (24 * 60 * 60)));
}

// Get recent milestones
$milestones = Environmental_Platform_Petitions_Database::get_petition_milestones($petition_id);

// Get momentum data
$momentum_data = Environmental_Platform_Petitions_Analytics::get_petition_momentum($petition_id);
?>

<div class="petition-progress-container" data-petition-id="<?php echo esc_attr($petition_id); ?>">
    <!-- Main Progress Display -->
    <div class="main-progress-section">
        <div class="progress-header">
            <div class="signature-display">
                <div class="current-count">
                    <span class="count-number" id="signature-count"><?php echo number_format($current_signatures); ?></span>
                    <span class="count-label">signatures</span>
                </div>
                <div class="goal-display">
                    <span class="goal-text">of <?php echo number_format($goal); ?> goal</span>
                    <span class="percentage">(<?php echo round($progress_percentage, 1); ?>%)</span>
                </div>
            </div>
            
            <?php if ($deadline): ?>
            <div class="deadline-info">
                <?php if ($days_remaining > 0): ?>
                    <div class="days-remaining">
                        <span class="days-number"><?php echo $days_remaining; ?></span>
                        <span class="days-label">days left</span>
                    </div>
                <?php else: ?>
                    <div class="deadline-passed">
                        <span class="status-text">Deadline Passed</span>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Main Progress Bar -->
        <div class="petition-progress-bar-container">
            <div class="progress-bar-wrapper">
                <div class="petition-progress-bar">
                    <div class="progress-fill" 
                         style="width: <?php echo $progress_percentage; ?>%"
                         data-percentage="<?php echo $progress_percentage; ?>">
                        <div class="progress-shine"></div>
                    </div>
                    <div class="progress-text">
                        <?php echo round($progress_percentage, 1); ?>% Complete
                    </div>
                </div>
                
                <!-- Milestone Markers -->
                <div class="milestone-markers">
                    <?php
                    $milestone_percentages = [25, 50, 75, 100];
                    foreach ($milestone_percentages as $percentage):
                        $milestone_count = ($goal * $percentage) / 100;
                        $achieved = $current_signatures >= $milestone_count;
                        $is_next = !$achieved && $percentage > $progress_percentage;
                    ?>
                        <div class="milestone-marker <?php echo $achieved ? 'achieved' : ($is_next ? 'next-target' : ''); ?>" 
                             style="left: <?php echo $percentage; ?>%"
                             data-count="<?php echo number_format($milestone_count); ?>"
                             data-percentage="<?php echo $percentage; ?>">
                            <div class="marker-dot"></div>
                            <div class="marker-label">
                                <span class="marker-count"><?php echo number_format($milestone_count); ?></span>
                                <?php if ($achieved): ?>
                                    <span class="marker-status">✓</span>
                                <?php elseif ($is_next): ?>
                                    <span class="marker-status">🎯</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Next Milestone Info -->
        <?php
        $next_milestone = null;
        foreach ($milestone_percentages as $percentage) {
            $milestone_count = ($goal * $percentage) / 100;
            if ($current_signatures < $milestone_count) {
                $next_milestone = [
                    'count' => $milestone_count,
                    'percentage' => $percentage,
                    'needed' => $milestone_count - $current_signatures
                ];
                break;
            }
        }
        ?>
        
        <?php if ($next_milestone): ?>
        <div class="next-milestone-info">
            <div class="milestone-target">
                <span class="target-text">Next Goal:</span>
                <span class="target-count"><?php echo number_format($next_milestone['count']); ?> signatures</span>
                <span class="needed-count">(<?php echo number_format($next_milestone['needed']); ?> more needed)</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Statistics Grid -->
    <div class="progress-statistics">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($today_signatures); ?></div>
                    <div class="stat-label">Today</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($week_signatures); ?></div>
                    <div class="stat-label">This Week</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo number_format($month_signatures); ?></div>
                    <div class="stat-label">This Month</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🌟</div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo count($milestones); ?></div>
                    <div class="stat-label">Milestones</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Momentum Indicator -->
    <?php if (!empty($momentum_data)): ?>
    <div class="momentum-section">
        <h4>📈 Petition Momentum</h4>
        <div class="momentum-indicator">
            <?php
            $momentum_class = 'neutral';
            $momentum_text = 'Steady';
            $momentum_icon = '➡️';
            
            if ($momentum_data['trend'] > 0.2) {
                $momentum_class = 'high';
                $momentum_text = 'Growing Fast';
                $momentum_icon = '🚀';
            } elseif ($momentum_data['trend'] > 0.1) {
                $momentum_class = 'medium';
                $momentum_text = 'Growing';
                $momentum_icon = '📈';
            } elseif ($momentum_data['trend'] < -0.1) {
                $momentum_class = 'low';
                $momentum_text = 'Slowing';
                $momentum_icon = '📉';
            }
            ?>
            <div class="momentum-badge <?php echo $momentum_class; ?>">
                <span class="momentum-icon"><?php echo $momentum_icon; ?></span>
                <span class="momentum-text"><?php echo $momentum_text; ?></span>
            </div>
            <div class="momentum-details">
                <span class="momentum-rate">
                    <?php echo number_format($momentum_data['avg_daily'], 1); ?> signatures/day average
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Milestones -->
    <?php if (!empty($milestones)): ?>
    <div class="milestones-section">
        <h4>🎯 Recent Milestones</h4>
        <div class="milestones-list">
            <?php foreach (array_slice($milestones, -3) as $milestone): ?>
                <div class="milestone-item">
                    <div class="milestone-icon">🎉</div>
                    <div class="milestone-content">
                        <div class="milestone-title">
                            <?php echo number_format($milestone['target_signatures']); ?> Signatures Reached!
                        </div>
                        <div class="milestone-date">
                            <?php echo human_time_diff(strtotime($milestone['achieved_date']), current_time('timestamp')); ?> ago
                        </div>
                        <?php if (!empty($milestone['message'])): ?>
                            <div class="milestone-message">
                                <?php echo esc_html($milestone['message']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Progress Chart (Mini) -->
    <div class="progress-chart-section">
        <h4>📊 Signature Growth</h4>
        <div class="mini-chart-container">
            <canvas id="petition-progress-chart" width="400" height="200"></canvas>
        </div>
        <div class="chart-legend">
            <div class="legend-item">
                <span class="legend-color" style="background-color: #2E8B57;"></span>
                <span class="legend-label">Daily Signatures</span>
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background-color: #228B22;"></span>
                <span class="legend-label">Cumulative Total</span>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="progress-cta-section">
        <div class="cta-content">
            <h4>Help Us Reach Our Goal!</h4>
            <p>Every signature brings us closer to making a real environmental impact.</p>
            
            <div class="cta-buttons">
                <?php if (!Environmental_Platform_Petitions_Database::has_user_signed($petition_id, get_current_user_id())): ?>
                    <a href="#petition-signature-form" class="cta-button primary">
                        <span class="button-icon">✍️</span>
                        Sign Now
                    </a>
                <?php endif; ?>
                
                <a href="#" class="cta-button secondary share-petition" data-petition-id="<?php echo $petition_id; ?>">
                    <span class="button-icon">📤</span>
                    Share
                </a>
                
                <a href="<?php echo get_permalink($petition_id); ?>" class="cta-button tertiary">
                    <span class="button-icon">📖</span>
                    Read More
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize progress tracking when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof PetitionProgress !== 'undefined') {
        PetitionProgress.init(<?php echo $petition_id; ?>);
    }
});
</script>
