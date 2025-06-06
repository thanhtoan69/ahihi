/**
 * Environmental Platform Performance Monitor
 * Real-time performance tracking and optimization
 */

(function($) {
    'use strict';
    
    // Performance monitoring class
    class PerformanceMonitor {
        constructor() {
            this.startTime = performance.now();
            this.metrics = {
                loadTime: 0,
                domReady: 0,
                resourcesLoaded: 0,
                memoryUsage: 0,
                connectionType: 'unknown'
            };
            
            this.init();
        }
        
        init() {
            this.detectConnectionType();
            this.measurePageLoad();
            this.monitorResourceLoading();
            this.trackMemoryUsage();
            this.setupPerformanceObserver();
            this.startRealTimeMonitoring();
        }
        
        detectConnectionType() {
            if ('connection' in navigator) {
                this.metrics.connectionType = navigator.connection.effectiveType || 'unknown';
            }
        }
        
        measurePageLoad() {
            // DOM Content Loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.metrics.domReady = performance.now() - this.startTime;
                });
            } else {
                this.metrics.domReady = performance.now() - this.startTime;
            }
            
            // Window loaded
            if (document.readyState === 'complete') {
                this.metrics.resourcesLoaded = performance.now() - this.startTime;
            } else {
                window.addEventListener('load', () => {
                    this.metrics.resourcesLoaded = performance.now() - this.startTime;
                    this.sendMetrics();
                });
            }
        }
        
        monitorResourceLoading() {
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.name.includes('.css') || entry.name.includes('.js')) {
                            this.trackResourceLoad(entry);
                        }
                    }
                });
                
                observer.observe({entryTypes: ['resource']});
            }
        }
        
        trackResourceLoad(entry) {
            const resourceData = {
                name: entry.name,
                duration: entry.duration,
                size: entry.transferSize || 0,
                type: this.getResourceType(entry.name)
            };
            
            // Log slow resources
            if (entry.duration > 1000) {
                console.warn('Slow resource detected:', resourceData);
                this.reportSlowResource(resourceData);
            }
        }
        
        getResourceType(url) {
            if (url.includes('.css')) return 'css';
            if (url.includes('.js')) return 'js';
            if (url.includes('.jpg') || url.includes('.png') || url.includes('.webp')) return 'image';
            return 'other';
        }
        
        trackMemoryUsage() {
            if ('memory' in performance) {
                this.metrics.memoryUsage = performance.memory.usedJSHeapSize;
                
                // Monitor memory usage periodically
                setInterval(() => {
                    const currentMemory = performance.memory.usedJSHeapSize;
                    if (currentMemory > this.metrics.memoryUsage * 1.5) {
                        console.warn('Memory usage increased significantly:', currentMemory);
                    }
                    this.metrics.memoryUsage = currentMemory;
                }, 30000); // Check every 30 seconds
            }
        }
        
        setupPerformanceObserver() {
            if ('PerformanceObserver' in window) {
                // Monitor Largest Contentful Paint
                const lcpObserver = new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const lastEntry = entries[entries.length - 1];
                    this.metrics.lcp = lastEntry.startTime;
                });
                lcpObserver.observe({entryTypes: ['largest-contentful-paint']});
                
                // Monitor First Input Delay
                const fidObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        this.metrics.fid = entry.processingStart - entry.startTime;
                    }
                });
                fidObserver.observe({entryTypes: ['first-input']});
                
                // Monitor Cumulative Layout Shift
                let clsValue = 0;
                const clsObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (!entry.hadRecentInput) {
                            clsValue += entry.value;
                        }
                    }
                    this.metrics.cls = clsValue;
                });
                clsObserver.observe({entryTypes: ['layout-shift']});
            }
        }
        
        startRealTimeMonitoring() {
            // Monitor FPS
            let lastTime = performance.now();
            let frameCount = 0;
            
            const measureFPS = () => {
                frameCount++;
                const currentTime = performance.now();
                
                if (currentTime - lastTime >= 1000) {
                    const fps = Math.round(frameCount * 1000 / (currentTime - lastTime));
                    this.metrics.fps = fps;
                    
                    if (fps < 30) {
                        console.warn('Low FPS detected:', fps);
                    }
                    
                    frameCount = 0;
                    lastTime = currentTime;
                }
                
                requestAnimationFrame(measureFPS);
            };
            
            requestAnimationFrame(measureFPS);
        }
        
        sendMetrics() {
            const finalMetrics = {
                ...this.metrics,
                loadTime: performance.now() - this.startTime,
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            };
            
            // Send to server
            if (typeof envPerf !== 'undefined') {
                $.ajax({
                    url: envPerf.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'env_track_performance',
                        metrics: JSON.stringify(finalMetrics),
                        nonce: envPerf.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('Performance metrics sent successfully');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to send performance metrics:', error);
                    }
                });
            }
        }
        
        reportSlowResource(resourceData) {
            if (typeof envPerf !== 'undefined') {
                $.ajax({
                    url: envPerf.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'env_report_slow_resource',
                        resource: JSON.stringify(resourceData),
                        nonce: envPerf.nonce
                    }
                });
            }
        }
        
        // Public methods for manual tracking
        trackCustomEvent(eventName, duration) {
            const eventData = {
                name: eventName,
                duration: duration,
                timestamp: performance.now()
            };
            
            if (typeof envPerf !== 'undefined') {
                $.ajax({
                    url: envPerf.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'env_track_custom_event',
                        event: JSON.stringify(eventData),
                        nonce: envPerf.nonce
                    }
                });
            }
        }
        
        measureUserInteraction(interactionType, element) {
            const startTime = performance.now();
            
            return {
                end: () => {
                    const duration = performance.now() - startTime;
                    this.trackCustomEvent(`user_interaction_${interactionType}`, duration);
                }
            };
        }
    }
    
    // Performance optimization utilities
    class PerformanceOptimizer {
        constructor() {
            this.init();
        }
        
        init() {
            this.optimizeImages();
            this.preloadCriticalResources();
            this.setupIntersectionObserver();
            this.optimizeScrolling();
        }
        
        optimizeImages() {
            // Add loading="lazy" to images without it
            const images = document.querySelectorAll('img:not([loading])');
            images.forEach(img => {
                if (this.isInViewport(img)) {
                    img.loading = 'eager';
                } else {
                    img.loading = 'lazy';
                }
            });
            
            // Convert to WebP if supported
            if (this.supportsWebP()) {
                this.convertToWebP();
            }
        }
        
        isInViewport(element) {
            const rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        }
        
        supportsWebP() {
            const canvas = document.createElement('canvas');
            canvas.width = 1;
            canvas.height = 1;
            return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
        }
        
        convertToWebP() {
            const images = document.querySelectorAll('img[src*=".jpg"], img[src*=".png"]');
            images.forEach(img => {
                const webpSrc = img.src.replace(/\.(jpg|png)$/, '.webp');
                
                // Test if WebP version exists
                const testImg = new Image();
                testImg.onload = () => {
                    img.src = webpSrc;
                };
                testImg.src = webpSrc;
            });
        }
        
        preloadCriticalResources() {
            // Preload critical CSS
            const criticalCSS = document.querySelectorAll('link[rel="stylesheet"][data-critical="true"]');
            criticalCSS.forEach(link => {
                const preloadLink = document.createElement('link');
                preloadLink.rel = 'preload';
                preloadLink.as = 'style';
                preloadLink.href = link.href;
                document.head.appendChild(preloadLink);
            });
            
            // Preload critical JavaScript
            const criticalJS = document.querySelectorAll('script[data-critical="true"]');
            criticalJS.forEach(script => {
                const preloadLink = document.createElement('link');
                preloadLink.rel = 'preload';
                preloadLink.as = 'script';
                preloadLink.href = script.src;
                document.head.appendChild(preloadLink);
            });
        }
        
        setupIntersectionObserver() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.loadLazyContent(entry.target);
                        }
                    });
                }, {
                    rootMargin: '50px'
                });
                
                // Observe lazy elements
                const lazyElements = document.querySelectorAll('[data-lazy]');
                lazyElements.forEach(element => {
                    observer.observe(element);
                });
            }
        }
        
        loadLazyContent(element) {
            if (element.dataset.lazy) {
                const content = element.dataset.lazy;
                
                if (element.tagName === 'IMG') {
                    element.src = content;
                } else if (element.tagName === 'IFRAME') {
                    element.src = content;
                } else {
                    // Load lazy HTML content
                    fetch(content)
                        .then(response => response.text())
                        .then(html => {
                            element.innerHTML = html;
                        });
                }
                
                element.removeAttribute('data-lazy');
            }
        }
        
        optimizeScrolling() {
            let ticking = false;
            
            const optimizedScroll = () => {
                // Perform scroll-based optimizations
                this.updateVisibleElements();
                ticking = false;
            };
            
            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(optimizedScroll);
                    ticking = true;
                }
            }, { passive: true });
        }
        
        updateVisibleElements() {
            // Pause videos not in viewport
            const videos = document.querySelectorAll('video');
            videos.forEach(video => {
                if (this.isInViewport(video)) {
                    if (video.paused && video.autoplay) {
                        video.play();
                    }
                } else {
                    if (!video.paused) {
                        video.pause();
                    }
                }
            });
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Initialize performance monitoring
        window.envPerformanceMonitor = new PerformanceMonitor();
        
        // Initialize performance optimizer
        window.envPerformanceOptimizer = new PerformanceOptimizer();
        
        // Track form submissions
        $('form').on('submit', function() {
            const tracker = window.envPerformanceMonitor.measureUserInteraction('form_submit', this);
            setTimeout(() => {
                tracker.end();
            }, 100);
        });
        
        // Track button clicks
        $('button, .btn').on('click', function() {
            const tracker = window.envPerformanceMonitor.measureUserInteraction('button_click', this);
            setTimeout(() => {
                tracker.end();
            }, 50);
        });
    });
    
})(jQuery);
