/**
 * Quiz Interface JavaScript
 * Handles interactive quiz functionality
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0 - Phase 40
 */

(function($) {
    'use strict';

    class QuizInterface {
        constructor() {
            this.currentSession = null;
            this.currentQuestion = 0;
            this.timeStarted = null;
            this.timeRemaining = null;
            this.timer = null;
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadQuizCategories();
        }

        bindEvents() {
            $(document).on('click', '.start-quiz-btn', this.startQuiz.bind(this));
            $(document).on('click', '.submit-answer-btn', this.submitAnswer.bind(this));
            $(document).on('click', '.next-question-btn', this.nextQuestion.bind(this));
            $(document).on('click', '.finish-quiz-btn', this.finishQuiz.bind(this));
            $(document).on('click', '.restart-quiz-btn', this.restartQuiz.bind(this));
            $(document).on('change', 'input[name="quiz-answer"]', this.handleAnswerSelection.bind(this));
        }

        loadQuizCategories() {
            const container = $('#env-quiz-interface');
            
            // Show category selection
            const categoriesHtml = `
                <div class="quiz-categories">
                    <h3>Choose Your Environmental Quiz Topic</h3>
                    <div class="category-grid">
                        <div class="category-card" data-category="1">
                            <div class="category-icon">🌱</div>
                            <h4>Climate Change</h4>
                            <p>Test your knowledge about global warming and climate science</p>
                            <button class="start-quiz-btn" data-category="1">Start Quiz</button>
                        </div>
                        <div class="category-card" data-category="2">
                            <div class="category-icon">♻️</div>
                            <h4>Waste Management</h4>
                            <p>Learn about recycling, composting, and waste reduction</p>
                            <button class="start-quiz-btn" data-category="2">Start Quiz</button>
                        </div>
                        <div class="category-card" data-category="3">
                            <div class="category-icon">💧</div>
                            <h4>Water Conservation</h4>
                            <p>Discover water-saving techniques and water quality issues</p>
                            <button class="start-quiz-btn" data-category="3">Start Quiz</button>
                        </div>
                        <div class="category-card" data-category="4">
                            <div class="category-icon">🔋</div>
                            <h4>Renewable Energy</h4>
                            <p>Explore solar, wind, and other sustainable energy sources</p>
                            <button class="start-quiz-btn" data-category="4">Start Quiz</button>
                        </div>
                        <div class="category-card" data-category="5">
                            <div class="category-icon">🌍</div>
                            <h4>Biodiversity</h4>
                            <p>Learn about ecosystems and wildlife conservation</p>
                            <button class="start-quiz-btn" data-category="5">Start Quiz</button>
                        </div>
                        <div class="category-card" data-category="6">
                            <div class="category-icon">🚗</div>
                            <h4>Sustainable Transport</h4>
                            <p>Discover eco-friendly transportation options</p>
                            <button class="start-quiz-btn" data-category="6">Start Quiz</button>
                        </div>
                    </div>
                </div>
            `;
            
            container.html(categoriesHtml);
        }

        startQuiz(e) {
            const categoryId = $(e.target).data('category');
            const container = $('#env-quiz-interface');
            
            container.html('<div class="quiz-loading"><div class="spinner"></div>Starting your quiz...</div>');
            
            $.ajax({
                url: envQuizAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'start_quiz',
                    category_id: categoryId,
                    nonce: envQuizAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.currentSession = response.data;
                        this.currentQuestion = 0;
                        this.timeStarted = Date.now();
                        this.timeRemaining = this.currentSession.time_limit * 60; // Convert to seconds
                        this.displayQuestion();
                        this.startTimer();
                    } else {
                        this.showError(response.message || 'Failed to start quiz');
                    }
                },
                error: () => {
                    this.showError('Network error. Please try again.');
                }
            });
        }

        displayQuestion() {
            const question = this.currentSession.questions[this.currentQuestion];
            const totalQuestions = this.currentSession.questions.length;
            const progress = ((this.currentQuestion + 1) / totalQuestions) * 100;
            
            let answersHtml = '';
            
            if (question.question_type === 'multiple_choice') {
                const options = JSON.parse(question.options);
                options.forEach((option, index) => {
                    answersHtml += `
                        <label class="answer-option">
                            <input type="radio" name="quiz-answer" value="${option}">
                            <span class="option-text">${option}</span>
                        </label>
                    `;
                });
            } else if (question.question_type === 'true_false') {
                answersHtml = `
                    <label class="answer-option">
                        <input type="radio" name="quiz-answer" value="true">
                        <span class="option-text">True</span>
                    </label>
                    <label class="answer-option">
                        <input type="radio" name="quiz-answer" value="false">
                        <span class="option-text">False</span>
                    </label>
                `;
            } else if (question.question_type === 'fill_blank') {
                answersHtml = `
                    <input type="text" name="quiz-answer" class="fill-blank-input" placeholder="Type your answer here...">
                `;
            }
            
            const questionHtml = `
                <div class="quiz-active">
                    <div class="quiz-header">
                        <div class="quiz-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progress}%"></div>
                            </div>
                            <span class="progress-text">Question ${this.currentQuestion + 1} of ${totalQuestions}</span>
                        </div>
                        <div class="quiz-timer" id="quiz-timer">
                            <span class="timer-icon">⏰</span>
                            <span class="timer-text">${this.formatTime(this.timeRemaining)}</span>
                        </div>
                    </div>
                    
                    <div class="quiz-question">
                        <h3 class="question-text">${question.question_text}</h3>
                        <div class="question-meta">
                            <span class="difficulty-badge difficulty-${question.difficulty}">${question.difficulty}</span>
                            <span class="points-badge">${question.points} points</span>
                        </div>
                    </div>
                    
                    <div class="quiz-answers">
                        ${answersHtml}
                    </div>
                    
                    <div class="quiz-controls">
                        <button class="submit-answer-btn" disabled>Submit Answer</button>
                        ${this.currentQuestion === totalQuestions - 1 ? 
                            '<button class="finish-quiz-btn" style="display:none;">Finish Quiz</button>' : 
                            '<button class="next-question-btn" style="display:none;">Next Question</button>'
                        }
                    </div>
                    
                    <div class="quiz-feedback" style="display:none;"></div>
                </div>
            `;
            
            $('#env-quiz-interface').html(questionHtml);
        }

        handleAnswerSelection() {
            const hasAnswer = $('input[name="quiz-answer"]:checked').length > 0 || 
                            $('input[name="quiz-answer"]').val().trim().length > 0;
            $('.submit-answer-btn').prop('disabled', !hasAnswer);
        }

        submitAnswer() {
            const answer = $('input[name="quiz-answer"]:checked').val() || 
                          $('input[name="quiz-answer"]').val().trim();
            
            if (!answer) return;
            
            const question = this.currentSession.questions[this.currentQuestion];
            
            $.ajax({
                url: envQuizAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'submit_quiz_answer',
                    session_id: this.currentSession.session_id,
                    question_id: question.question_id,
                    answer: answer,
                    nonce: envQuizAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showAnswerFeedback(response.data);
                    } else {
                        this.showError(response.message || 'Failed to submit answer');
                    }
                },
                error: () => {
                    this.showError('Network error. Please try again.');
                }
            });
        }

        showAnswerFeedback(data) {
            const feedbackHtml = `
                <div class="answer-result ${data.is_correct ? 'correct' : 'incorrect'}">
                    <div class="result-icon">${data.is_correct ? '✅' : '❌'}</div>
                    <div class="result-text">
                        <strong>${data.is_correct ? 'Correct!' : 'Incorrect'}</strong>
                        ${data.explanation ? `<p class="explanation">${data.explanation}</p>` : ''}
                        <p class="points-earned">Points earned: ${data.points_earned}</p>
                    </div>
                </div>
            `;
            
            $('.quiz-feedback').html(feedbackHtml).show();
            $('.submit-answer-btn').hide();
            
            if (this.currentQuestion === this.currentSession.questions.length - 1) {
                $('.finish-quiz-btn').show();
            } else {
                $('.next-question-btn').show();
            }
            
            // Disable answer selection
            $('input[name="quiz-answer"]').prop('disabled', true);
        }

        nextQuestion() {
            this.currentQuestion++;
            this.displayQuestion();
        }

        finishQuiz() {
            $.ajax({
                url: envQuizAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'complete_quiz',
                    session_id: this.currentSession.session_id,
                    nonce: envQuizAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showQuizResults(response.data);
                        this.stopTimer();
                    } else {
                        this.showError(response.message || 'Failed to complete quiz');
                    }
                },
                error: () => {
                    this.showError('Network error. Please try again.');
                }
            });
        }

        showQuizResults(results) {
            const percentage = (results.correct_answers / results.total_questions) * 100;
            let performanceLevel = 'poor';
            
            if (percentage >= 80) performanceLevel = 'excellent';
            else if (percentage >= 60) performanceLevel = 'good';
            else if (percentage >= 40) performanceLevel = 'fair';
            
            const resultsHtml = `
                <div class="quiz-results">
                    <div class="results-header">
                        <h2>Quiz Complete!</h2>
                        <div class="performance-badge performance-${performanceLevel}">
                            ${performanceLevel.toUpperCase()}
                        </div>
                    </div>
                    
                    <div class="results-stats">
                        <div class="stat-item">
                            <span class="stat-label">Score</span>
                            <span class="stat-value">${Math.round(percentage)}%</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Correct Answers</span>
                            <span class="stat-value">${results.correct_answers}/${results.total_questions}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Points Earned</span>
                            <span class="stat-value">${results.total_points}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Time Taken</span>
                            <span class="stat-value">${this.formatTime(results.time_taken)}</span>
                        </div>
                    </div>
                    
                    ${results.achievements ? `
                        <div class="new-achievements">
                            <h3>🏆 New Achievements Unlocked!</h3>
                            <div class="achievement-list">
                                ${results.achievements.map(achievement => `
                                    <div class="achievement-item">
                                        <span class="achievement-icon">${achievement.icon}</span>
                                        <span class="achievement-name">${achievement.name}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="quiz-actions">
                        <button class="restart-quiz-btn primary">Take Another Quiz</button>
                        <button class="view-leaderboard-btn secondary">View Leaderboard</button>
                    </div>
                </div>
            `;
            
            $('#env-quiz-interface').html(resultsHtml);
        }

        restartQuiz() {
            this.currentSession = null;
            this.currentQuestion = 0;
            this.stopTimer();
            this.loadQuizCategories();
        }

        startTimer() {
            this.timer = setInterval(() => {
                this.timeRemaining--;
                $('#quiz-timer .timer-text').text(this.formatTime(this.timeRemaining));
                
                if (this.timeRemaining <= 0) {
                    this.stopTimer();
                    this.finishQuiz();
                }
            }, 1000);
        }

        stopTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }

        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        showError(message) {
            const errorHtml = `
                <div class="quiz-error">
                    <div class="error-icon">⚠️</div>
                    <div class="error-message">${message}</div>
                    <button class="restart-quiz-btn">Try Again</button>
                </div>
            `;
            $('#env-quiz-interface').html(errorHtml);
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('#env-quiz-interface').length) {
            new QuizInterface();
        }
    });

})(jQuery);
