-- Marketplace Insights Analytics View
CREATE OR REPLACE VIEW marketplace_insights_final AS
SELECT 
    p.product_id,
    p.product_name,
    p.price,
    p.eco_score,
    p.category_id,
    p.seller_id,
    p.stock_quantity,
    p.status as product_status,
    p.created_at as product_launch_date,
    
    -- Sales Metrics
    COALESCE(sales_stats.total_orders, 0) as total_orders,
    COALESCE(sales_stats.total_revenue, 0) as total_revenue,
    COALESCE(sales_stats.avg_order_value, 0) as avg_order_value,
    COALESCE(sales_stats.unique_customers, 0) as unique_customers,
    
    -- Review Metrics  
    COALESCE(review_stats.total_reviews, 0) as total_reviews,
    COALESCE(review_stats.avg_rating, 0) as avg_rating,
    
    -- Performance Metrics
    CASE 
        WHEN COALESCE(sales_stats.total_orders, 0) >= 50 THEN 'Best Seller'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 20 THEN 'Popular'
        WHEN COALESCE(sales_stats.total_orders, 0) >= 5 THEN 'Moderate'
        ELSE 'Low Sales'
    END as sales_performance,
    
    -- Days since launch
    DATEDIFF(CURDATE(), p.created_at) as days_since_launch,
    
    -- Green Product Score
    (p.eco_score * 0.3 + 
     COALESCE(review_stats.avg_rating, 0) * 20 + 
     LEAST(COALESCE(sales_stats.total_orders, 0), 100)) as green_product_score

FROM products p
LEFT JOIN (
    SELECT 
        oi.product_id,
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(oi.quantity * oi.unit_price) as total_revenue,
        AVG(oi.quantity * oi.unit_price) as avg_order_value,
        COUNT(DISTINCT o.user_id) as unique_customers
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.order_status IN ('delivered', 'confirmed')
    GROUP BY oi.product_id
) sales_stats ON p.product_id = sales_stats.product_id

LEFT JOIN (
    SELECT 
        product_id,
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating
    FROM product_reviews 
    GROUP BY product_id
) review_stats ON p.product_id = review_stats.product_id;
