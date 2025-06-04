-- ================================================================
-- Phase 22: Performance Optimization Indexes
-- Environmental Platform Database Enhancement
-- ================================================================

-- User Session Performance Indexes
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_activity ON user_sessions(user_id, last_activity);
CREATE INDEX IF NOT EXISTS idx_user_sessions_device ON user_sessions(device_type, created_at);
CREATE INDEX IF NOT EXISTS idx_user_sessions_active ON user_sessions(is_active, last_activity);

-- Article Interaction Performance Indexes
CREATE INDEX IF NOT EXISTS idx_article_interactions_user_type ON article_interactions(user_id, interaction_type);
CREATE INDEX IF NOT EXISTS idx_article_interactions_article_created ON article_interactions(article_id, created_at);
CREATE INDEX IF NOT EXISTS idx_article_interactions_type_date ON article_interactions(interaction_type, created_at);
CREATE INDEX IF NOT EXISTS idx_article_interactions_user_article ON article_interactions(user_id, article_id);

-- Carbon Footprint Performance Indexes
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_user_date ON carbon_footprints(user_id, activity_date);
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_category ON carbon_footprints(activity_category, created_at);
CREATE INDEX IF NOT EXISTS idx_carbon_footprints_user_category ON carbon_footprints(user_id, activity_category);

-- Quiz Session Performance Indexes
CREATE INDEX IF NOT EXISTS idx_quiz_sessions_user_status ON quiz_sessions(user_id, status);
CREATE INDEX IF NOT EXISTS idx_quiz_sessions_type_date ON quiz_sessions(session_type, started_at);
CREATE INDEX IF NOT EXISTS idx_quiz_sessions_user_category ON quiz_sessions(user_id, category_id);

-- Order Performance Indexes
CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, order_status);
CREATE INDEX IF NOT EXISTS idx_orders_seller_date ON orders(seller_id, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_status_date ON orders(order_status, created_at);
CREATE INDEX IF NOT EXISTS idx_orders_user_date ON orders(user_id, created_at);

-- Product Performance Indexes
CREATE INDEX IF NOT EXISTS idx_products_category_status ON products(category_id, status);
CREATE INDEX IF NOT EXISTS idx_products_eco_score ON products(eco_score, status);
CREATE INDEX IF NOT EXISTS idx_products_seller_status ON products(seller_id, status);
CREATE INDEX IF NOT EXISTS idx_products_price_status ON products(price, status);

-- Order Items Performance Indexes
CREATE INDEX IF NOT EXISTS idx_order_items_product ON order_items(product_id);
CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items(order_id, product_id);

-- Product Reviews Performance Indexes
CREATE INDEX IF NOT EXISTS idx_product_reviews_product_rating ON product_reviews(product_id, rating);
CREATE INDEX IF NOT EXISTS idx_product_reviews_user ON product_reviews(user_id, created_at);

-- Article Performance Indexes
CREATE INDEX IF NOT EXISTS idx_articles_author_date ON articles(author_id, created_at);
CREATE INDEX IF NOT EXISTS idx_articles_category_status ON articles(category_id, status);

-- User Performance Indexes
CREATE INDEX IF NOT EXISTS idx_users_type_active ON users(user_type, is_active);
CREATE INDEX IF NOT EXISTS idx_users_created ON users(created_at);

SELECT 'Phase 22 Performance Indexes Created Successfully' as status;
