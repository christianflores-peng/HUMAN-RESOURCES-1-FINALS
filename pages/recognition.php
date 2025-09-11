<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get current user info
$current_user = $_SESSION['username'] ?? 'User';
$current_role = $_SESSION['role'] ?? 'Employee';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Social Recognition</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<?php 
$active_page = 'recognition';
$page_title = 'Social Recognition';
include '../partials/sidebar.php';
include '../partials/header.php';
?>
            <!-- Social Recognition Module -->
            <div id="recognition-module" class="module active">
                <div class="module-header">
                    <h2>Social Recognition</h2>
                    <button class="btn btn-primary" onclick="showSubModule('peer-recognition')">Give Recognition</button>
                </div>

                <div class="submodule-nav">
                    <button class="submodule-btn active" data-submodule="peer-recognition">Peer Recognition</button>
                    <button class="submodule-btn" data-submodule="rewards">Rewards</button>
                    <button class="submodule-btn" data-submodule="analytics">Engagement Analytics</button>
                </div>

                <!-- Peer Recognition Submodule -->
                <div id="peer-recognition" class="submodule active">
                    <div class="recognition-container">
                        <h3>Give Recognition</h3>
                        <div class="recognition-form">
                            <form class="recognition-creation-form">
                                <div class="form-group">
                                    <label>Recognize Employee</label>
                                    <select>
                                        <option>Select Employee</option>
                                        <option>John Smith</option>
                                        <option>Sarah Johnson</option>
                                        <option>Mike Davis</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Recognition Type</label>
                                    <div class="recognition-types">
                                        <button type="button" class="recognition-type-btn" data-type="teamwork">
                                            🤝 Teamwork
                                        </button>
                                        <button type="button" class="recognition-type-btn" data-type="innovation">
                                            💡 Innovation
                                        </button>
                                        <button type="button" class="recognition-type-btn" data-type="leadership">
                                            👑 Leadership
                                        </button>
                                        <button type="button" class="recognition-type-btn" data-type="excellence">
                                            ⭐ Excellence
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea rows="3" placeholder="Share why this person deserves recognition..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox"> Make this recognition public
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Recognition</button>
                            </form>
                        </div>

                        <div class="recognition-feed">
                            <h4>Recent Recognition</h4>
                            <div class="recognition-list">
                                <div class="recognition-item">
                                    <div class="recognition-header">
                                        <div class="recognition-avatar">👤</div>
                                        <div class="recognition-info">
                                            <strong>Sarah Johnson</strong> recognized <strong>John Smith</strong>
                                            <small>2 hours ago</small>
                                        </div>
                                        <div class="recognition-badge">🤝 Teamwork</div>
                                    </div>
                                    <p>"John went above and beyond to help our team meet the deadline. His collaboration and support made all the difference!"</p>
                                    <div class="recognition-actions">
                                        <button class="btn btn-sm">👍 12</button>
                                        <button class="btn btn-sm">💬 Comment</button>
                                    </div>
                                </div>
                                
                                <div class="recognition-item">
                                    <div class="recognition-header">
                                        <div class="recognition-avatar">👤</div>
                                        <div class="recognition-info">
                                            <strong>Mike Davis</strong> recognized <strong>Lisa Wilson</strong>
                                            <small>1 day ago</small>
                                        </div>
                                        <div class="recognition-badge">💡 Innovation</div>
                                    </div>
                                    <p>"Lisa's creative solution saved us weeks of development time. Brilliant thinking!"</p>
                                    <div class="recognition-actions">
                                        <button class="btn btn-sm">👍 8</button>
                                        <button class="btn btn-sm">💬 Comment</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rewards Submodule -->
                <div id="rewards" class="submodule">
                    <div class="rewards-container">
                        <h3>Rewards Program</h3>
                        <div class="rewards-overview">
                            <div class="reward-stat">
                                <h4>Total Points Awarded</h4>
                                <p class="stat-number">12,450</p>
                            </div>
                            <div class="reward-stat">
                                <h4>Active Participants</h4>
                                <p class="stat-number">89%</p>
                            </div>
                            <div class="reward-stat">
                                <h4>Rewards Redeemed</h4>
                                <p class="stat-number">156</p>
                            </div>
                        </div>

                        <div class="rewards-catalog">
                            <h4>Rewards Catalog</h4>
                            <div class="rewards-grid">
                                <div class="reward-item">
                                    <div class="reward-icon">🎁</div>
                                    <h5>Gift Card</h5>
                                    <p>$50 Amazon Gift Card</p>
                                    <span class="reward-points">500 points</span>
                                    <button class="btn btn-sm">Redeem</button>
                                </div>
                                
                                <div class="reward-item">
                                    <div class="reward-icon">🏖️</div>
                                    <h5>Extra PTO Day</h5>
                                    <p>Additional paid time off</p>
                                    <span class="reward-points">1000 points</span>
                                    <button class="btn btn-sm">Redeem</button>
                                </div>
                                
                                <div class="reward-item">
                                    <div class="reward-icon">🍕</div>
                                    <h5>Team Lunch</h5>
                                    <p>Lunch for your team</p>
                                    <span class="reward-points">750 points</span>
                                    <button class="btn btn-sm">Redeem</button>
                                </div>
                                
                                <div class="reward-item">
                                    <div class="reward-icon">🎓</div>
                                    <h5>Training Course</h5>
                                    <p>Professional development</p>
                                    <span class="reward-points">1500 points</span>
                                    <button class="btn btn-sm">Redeem</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Submodule -->
                <div id="analytics" class="submodule">
                    <div class="analytics-container">
                        <h3>Engagement Analytics</h3>
                        <div class="analytics-grid">
                            <div class="analytics-card">
                                <h4>Recognition Trends</h4>
                                <div class="chart-placeholder">
                                    <div class="trend-chart">
                                        <div class="chart-bar" style="height: 60%"></div>
                                        <div class="chart-bar" style="height: 80%"></div>
                                        <div class="chart-bar" style="height: 45%"></div>
                                        <div class="chart-bar" style="height: 90%"></div>
                                        <div class="chart-bar" style="height: 70%"></div>
                                    </div>
                                </div>
                                <p>Recognition activity over the last 5 months</p>
                            </div>
                            
                            <div class="analytics-card">
                                <h4>Top Recognizers</h4>
                                <div class="leaderboard">
                                    <div class="leaderboard-item">
                                        <span class="rank">1</span>
                                        <span class="name">Sarah Johnson</span>
                                        <span class="count">23</span>
                                    </div>
                                    <div class="leaderboard-item">
                                        <span class="rank">2</span>
                                        <span class="name">Mike Davis</span>
                                        <span class="count">19</span>
                                    </div>
                                    <div class="leaderboard-item">
                                        <span class="rank">3</span>
                                        <span class="name">John Smith</span>
                                        <span class="count">15</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="analytics-card">
                                <h4>Recognition Categories</h4>
                                <div class="category-stats">
                                    <div class="category-item">
                                        <span class="category-name">🤝 Teamwork</span>
                                        <div class="category-bar">
                                            <div class="category-fill" style="width: 35%"></div>
                                        </div>
                                        <span class="category-percent">35%</span>
                                    </div>
                                    <div class="category-item">
                                        <span class="category-name">💡 Innovation</span>
                                        <div class="category-bar">
                                            <div class="category-fill" style="width: 28%"></div>
                                        </div>
                                        <span class="category-percent">28%</span>
                                    </div>
                                    <div class="category-item">
                                        <span class="category-name">⭐ Excellence</span>
                                        <div class="category-bar">
                                            <div class="category-fill" style="width: 22%"></div>
                                        </div>
                                        <span class="category-percent">22%</span>
                                    </div>
                                    <div class="category-item">
                                        <span class="category-name">👑 Leadership</span>
                                        <div class="category-bar">
                                            <div class="category-fill" style="width: 15%"></div>
                                        </div>
                                        <span class="category-percent">15%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
<?php include '../partials/footer.php'; ?>
