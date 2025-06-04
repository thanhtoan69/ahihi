/**
 * Challenge Dashboard JavaScript
 * Handles challenge system interactions
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0 - Phase 40
 */

(function($) {
    'use strict';

    class ChallengeDashboard {
        constructor() {
            this.activeTab = 'available';
            this.userChallenges = [];
            this.availableChallenges = [];
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadAvailableChallenges();
        }

        bindEvents() {
            $(document).on('click', '.tab-btn', this.switchTab.bind(this));
            $(document).on('click', '.join-challenge-btn', this.joinChallenge.bind(this));
            $(document).on('click', '.update-progress-btn', this.updateProgress.bind(this));
            $(document).on('click', '.complete-challenge-btn', this.completeChallenge.bind(this));
            $(document).on('click', '.challenge-details-btn', this.showChallengeDetails.bind(this));
            $(document).on('change', '.progress-input', this.handleProgressInput.bind(this));
        }

        switchTab(e) {
            const tab = $(e.target).data('tab');
            this.activeTab = tab;
            
            $('.tab-btn').removeClass('active');
            $(e.target).addClass('active');
            
            switch(tab) {
                case 'available':
                    this.loadAvailableChallenges();
                    break;
                case 'my-challenges':
                    this.loadUserChallenges();
                    break;
                case 'completed':
                    this.loadCompletedChallenges();
                    break;
            }
        }

        loadAvailableChallenges() {
            this.showLoading();
            
            $.ajax({
                url: envChallengeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_available_challenges'
                },
                success: (response) => {
                    if (response.success) {
                        this.availableChallenges = response.data;
                        this.renderAvailableChallenges();
                    } else {
                        this.showError('Failed to load challenges');
                    }
                },
                error: () => {
                    this.showError('Network error');
                }
            });
        }

        loadUserChallenges() {
            this.showLoading();
            
            $.ajax({
                url: envChallengeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_user_challenges'
                },
                success: (response) => {
                    if (response.success) {
                        this.userChallenges = response.data;
                        this.renderUserChallenges();
                    } else {
                        this.showError('Failed to load your challenges');
                    }
                },
                error: () => {
                    this.showError('Network error');
                }
            });
        }

        loadCompletedChallenges() {
            this.showLoading();
            
            $.ajax({
                url: envChallengeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_user_challenges',
                    status: 'completed'
                },
                success: (response) => {
                    if (response.success) {
                        const completedChallenges = response.data.filter(c => c.is_completed);
                        this.renderCompletedChallenges(completedChallenges);
                    } else {
                        this.showError('Failed to load completed challenges');
                    }
                },
                error: () => {
                    this.showError('Network error');
                }
            });
        }

        renderAvailableChallenges() {
            if (!this.availableChallenges.length) {
                this.showMessage('No challenges available at the moment. Check back soon!');
                return;
            }

            const challengesHtml = this.availableChallenges.map(challenge => this.renderChallengeCard(challenge, 'available')).join('');
            
            const html = `
                <div class="challenges-grid">
                    ${challengesHtml}
                </div>
            `;
            
            $('.challenge-content').html(html);
        }

        renderUserChallenges() {
            if (!this.userChallenges.length) {
                this.showMessage('You haven\'t joined any challenges yet. Check out the available challenges!');
                return;
            }

            const activeChallenges = this.userChallenges.filter(c => !c.is_completed);
            const challengesHtml = activeChallenges.map(challenge => this.renderChallengeCard(challenge, 'active')).join('');
            
            const html = `
                <div class="challenges-grid">
                    ${challengesHtml}
                </div>
            `;
            
            $('.challenge-content').html(html);
        }

        renderCompletedChallenges(challenges) {
            if (!challenges.length) {
                this.showMessage('No completed challenges yet. Keep working on your active challenges!');
                return;
            }

            const challengesHtml = challenges.map(challenge => this.renderChallengeCard(challenge, 'completed')).join('');
            
            const html = `
                <div class="challenges-grid">
                    ${challengesHtml}
                </div>
            `;
            
            $('.challenge-content').html(html);
        }

        renderChallengeCard(challenge, type) {
            const categoryIcons = {
                'carbon': '🌱',
                'waste': '♻️',
                'energy': '⚡',
                'water': '💧',
                'transport': '🚗',
                'consumption': '🛒',
                'education': '📚',
                'social': '👥'
            };

            const difficultyColors = {
                'easy': 'green',
                'medium': 'orange',
                'hard': 'red',
                'expert': 'purple'
            };

            let progressHtml = '';
            let actionsHtml = '';
            
            if (type === 'available') {
                actionsHtml = `
                    <div class="challenge-actions">
                        <button class="join-challenge-btn" data-challenge-id="${challenge.challenge_id}">
                            Join Challenge
                        </button>
                        <button class="challenge-details-btn" data-challenge-id="${challenge.challenge_id}">
                            View Details
                        </button>
                    </div>
                `;
            } else if (type === 'active') {
                const progress = this.calculateProgress(challenge);
                progressHtml = `
                    <div class="challenge-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progress.percentage}%"></div>
                        </div>
                        <span class="progress-text">${progress.percentage}% Complete</span>
                    </div>
                `;
                
                actionsHtml = `
                    <div class="challenge-actions">
                        <button class="update-progress-btn" data-challenge-id="${challenge.challenge_id}">
                            Update Progress
                        </button>
                        ${progress.percentage >= 100 ? `
                            <button class="complete-challenge-btn" data-challenge-id="${challenge.challenge_id}">
                                Complete Challenge
                            </button>
                        ` : ''}
                    </div>
                `;
            } else if (type === 'completed') {
                progressHtml = `
                    <div class="challenge-completed">
                        <span class="completion-badge">✅ Completed</span>
                        <span class="completion-date">Completed: ${this.formatDate(challenge.completed_at)}</span>
                        <span class="points-earned">+${challenge.points_earned} points</span>
                    </div>
                `;
            }

            return `
                <div class="challenge-card ${type}" data-challenge-id="${challenge.challenge_id}">
                    <div class="challenge-header">
                        <div class="challenge-icon">${categoryIcons[challenge.category] || '🌍'}</div>
                        <div class="challenge-meta">
                            <span class="challenge-type">${challenge.challenge_type.toUpperCase()}</span>
                            <span class="challenge-difficulty difficulty-${challenge.difficulty_level}" 
                                  style="background-color: ${difficultyColors[challenge.difficulty_level]}">
                                ${challenge.difficulty_level.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    
                    <div class="challenge-content">
                        <h3 class="challenge-title">${challenge.challenge_name}</h3>
                        <p class="challenge-description">${challenge.challenge_description}</p>
                        
                        ${progressHtml}
                        
                        <div class="challenge-timeline">
                            <span class="timeline-item">
                                <strong>Starts:</strong> ${this.formatDate(challenge.start_date)}
                            </span>
                            <span class="timeline-item">
                                <strong>Ends:</strong> ${this.formatDate(challenge.end_date)}
                            </span>
                        </div>
                        
                        <div class="challenge-rewards">
                            <h4>Rewards:</h4>
                            ${this.renderRewards(challenge.rewards)}
                        </div>
                    </div>
                    
                    ${actionsHtml}
                </div>
            `;
        }

        calculateProgress(challenge) {
            const requirements = JSON.parse(challenge.requirements || '{}');
            const progress = JSON.parse(challenge.progress || '{}');
            
            let totalRequirements = 0;
            let completedRequirements = 0;
            
            Object.keys(requirements).forEach(key => {
                totalRequirements += requirements[key];
                completedRequirements += Math.min(progress[key] || 0, requirements[key]);
            });
            
            const percentage = totalRequirements > 0 ? Math.round((completedRequirements / totalRequirements) * 100) : 0;
            
            return {
                percentage: percentage,
                completed: completedRequirements,
                total: totalRequirements
            };
        }

        renderRewards(rewardsJson) {
            const rewards = JSON.parse(rewardsJson || '{}');
            let html = '<ul class="rewards-list">';
            
            if (rewards.points) {
                html += `<li>🎯 ${rewards.points} Points</li>`;
            }
            if (rewards.badge) {
                html += `<li>🏆 ${rewards.badge} Badge</li>`;
            }
            if (rewards.achievement) {
                html += `<li>⭐ ${rewards.achievement} Achievement</li>`;
            }
            if (rewards.custom) {
                html += `<li>🎁 ${rewards.custom}</li>`;
            }
            
            html += '</ul>';
            return html;
        }

        joinChallenge(e) {
            const challengeId = $(e.target).data('challenge-id');
            
            $.ajax({
                url: envChallengeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'participate_in_challenge',
                    challenge_id: challengeId,
                    nonce: envChallengeAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Successfully joined the challenge!');
                        // Refresh the current view
                        if (this.activeTab === 'available') {
                            this.loadAvailableChallenges();
                        }
                    } else {
                        this.showError(response.message || 'Failed to join challenge');
                    }
                },
                error: () => {
                    this.showError('Network error');
                }
            });
        }

        updateProgress(e) {
            const challengeId = $(e.target).data('challenge-id');
            
            // Show progress update modal
            this.showProgressModal(challengeId);
        }

        showProgressModal(challengeId) {
            const challenge = this.userChallenges.find(c => c.challenge_id == challengeId);
            if (!challenge) return;
            
            const requirements = JSON.parse(challenge.requirements || '{}');
            const currentProgress = JSON.parse(challenge.progress || '{}');
            
            let inputsHtml = '';
            Object.keys(requirements).forEach(key => {
                inputsHtml += `
                    <div class="progress-input-group">
                        <label for="progress-${key}">${this.formatRequirementName(key)}:</label>
                        <input type="number" 
                               id="progress-${key}" 
                               name="${key}" 
                               value="${currentProgress[key] || 0}" 
                               max="${requirements[key]}"
                               class="progress-input">
                        <span class="progress-max">/ ${requirements[key]}</span>
                    </div>
                `;
            });
            
            const modalHtml = `
                <div class="challenge-modal-overlay">
                    <div class="challenge-modal">
                        <div class="modal-header">
                            <h3>Update Progress: ${challenge.challenge_name}</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-content">
                            <form id="progress-form" data-challenge-id="${challengeId}">
                                ${inputsHtml}
                                <div class="modal-actions">
                                    <button type="button" class="modal-close secondary">Cancel</button>
                                    <button type="submit" class="primary">Update Progress</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Bind modal events
            $('.modal-close').on('click', () => $('.challenge-modal-overlay').remove());
            $('#progress-form').on('submit', this.submitProgressUpdate.bind(this));
        }

        submitProgressUpdate(e) {
            e.preventDefault();
            
            const challengeId = $(e.target).data('challenge-id');
            const formData = {};
            
            $(e.target).find('.progress-input').each(function() {
                formData[$(this).attr('name')] = parseInt($(this).val()) || 0;
            });
            
            $.ajax({
                url: envChallengeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_challenge_progress',
                    challenge_id: challengeId,
                    progress: formData,
                    nonce: envChallengeAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Progress updated successfully!');
                        $('.challenge-modal-overlay').remove();
                        this.loadUserChallenges();
                    } else {
                        this.showError(response.message || 'Failed to update progress');
                    }
                },
                error: () => {
                    this.showError('Network error');
                }
            });
        }

        completeChallenge(e) {
            const challengeId = $(e.target).data('challenge-id');
            
            if (!confirm('Are you sure you want to complete this challenge? This action cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: envChallengeAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'complete_challenge',
                    challenge_id: challengeId,
                    nonce: envChallengeAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Challenge completed! Congratulations!');
                        this.loadUserChallenges();
                        
                        // Show completion celebration
                        this.showCompletionCelebration(response.data);
                    } else {
                        this.showError(response.message || 'Failed to complete challenge');
                    }
                },
                error: () => {
                    this.showError('Network error');
                }
            });
        }

        showCompletionCelebration(data) {
            const celebrationHtml = `
                <div class="completion-celebration">
                    <div class="celebration-content">
                        <div class="celebration-icon">🎉</div>
                        <h2>Challenge Completed!</h2>
                        <p>Congratulations on completing your environmental challenge!</p>
                        
                        <div class="rewards-earned">
                            <h3>Rewards Earned:</h3>
                            <ul>
                                <li>🎯 ${data.points_earned} Points</li>
                                ${data.badge_earned ? `<li>🏆 ${data.badge_earned} Badge</li>` : ''}
                                ${data.achievements ? data.achievements.map(a => `<li>⭐ ${a.name} Achievement</li>`).join('') : ''}
                            </ul>
                        </div>
                        
                        <button class="close-celebration">Continue</button>
                    </div>
                </div>
            `;
            
            $('body').append(celebrationHtml);
            
            $('.close-celebration').on('click', () => $('.completion-celebration').remove());
            
            // Auto-close after 10 seconds
            setTimeout(() => {
                $('.completion-celebration').fadeOut();
            }, 10000);
        }

        formatRequirementName(key) {
            const names = {
                'carbon_reduced': 'Carbon Reduced (kg CO2)',
                'waste_classified': 'Items Classified',
                'quizzes_completed': 'Quizzes Completed',
                'days_active': 'Days Active',
                'points_earned': 'Points Earned',
                'energy_saved': 'Energy Saved (kWh)',
                'water_saved': 'Water Saved (L)',
                'actions_completed': 'Actions Completed'
            };
            
            return names[key] || key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        showLoading() {
            $('.challenge-content').html('<div class="challenges-loading"><div class="spinner"></div>Loading challenges...</div>');
        }

        showMessage(message) {
            $('.challenge-content').html(`<div class="challenges-message">${message}</div>`);
        }

        showError(message) {
            $('.challenge-content').html(`<div class="challenges-error">❌ ${message}</div>`);
        }

        showSuccess(message) {
            // Create a temporary success notification
            const notification = $(`<div class="success-notification">${message}</div>`);
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 3000);
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#env-challenge-dashboard').length) {
            new ChallengeDashboard();
        }
    });

})(jQuery);
