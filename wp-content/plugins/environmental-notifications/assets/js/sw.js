/**
 * Environmental Notifications - Service Worker
 * Handles push notifications and background sync
 */

const CACHE_NAME = 'environmental-notifications-v1';
const CACHE_URLS = [
    '/wp-content/plugins/environmental-notifications/assets/css/frontend.css',
    '/wp-content/plugins/environmental-notifications/assets/js/frontend.js'
];

// Install event - cache resources
self.addEventListener('install', function(event) {
    console.log('Service Worker installing');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(CACHE_URLS);
            })
            .then(function() {
                return self.skipWaiting();
            })
    );
});

// Activate event - cleanup old caches
self.addEventListener('activate', function(event) {
    console.log('Service Worker activating');
    
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(function() {
            return self.clients.claim();
        })
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', function(event) {
    console.log('Push message received:', event);

    let notificationData = {
        title: 'Environmental Alert',
        body: 'You have a new notification',
        icon: '/wp-content/plugins/environmental-notifications/assets/images/icon-192.png',
        badge: '/wp-content/plugins/environmental-notifications/assets/images/badge-72.png',
        tag: 'environmental-notification',
        renotify: true,
        requireInteraction: false,
        actions: [
            {
                action: 'view',
                title: 'View',
                icon: '/wp-content/plugins/environmental-notifications/assets/images/view-icon.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/wp-content/plugins/environmental-notifications/assets/images/dismiss-icon.png'
            }
        ],
        data: {
            url: '/',
            timestamp: Date.now()
        }
    };

    // Parse push data if available
    if (event.data) {
        try {
            const pushData = event.data.json();
            notificationData = {
                ...notificationData,
                ...pushData,
                data: {
                    ...notificationData.data,
                    ...pushData.data
                }
            };
        } catch (e) {
            console.log('Failed to parse push data:', e);
            notificationData.body = event.data.text();
        }
    }

    // Customize notification based on type
    if (notificationData.type) {
        switch (notificationData.type) {
            case 'alert':
                notificationData.icon = '/wp-content/plugins/environmental-notifications/assets/images/alert-icon.png';
                notificationData.requireInteraction = true;
                notificationData.vibrate = [200, 100, 200];
                break;
            case 'warning':
                notificationData.icon = '/wp-content/plugins/environmental-notifications/assets/images/warning-icon.png';
                notificationData.vibrate = [100, 50, 100];
                break;
            case 'info':
                notificationData.icon = '/wp-content/plugins/environmental-notifications/assets/images/info-icon.png';
                break;
            case 'message':
                notificationData.icon = '/wp-content/plugins/environmental-notifications/assets/images/message-icon.png';
                notificationData.tag = 'environmental-message';
                break;
        }
    }

    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
    );
});

// Notification click event
self.addEventListener('notificationclick', function(event) {
    console.log('Notification clicked:', event.notification);

    const notification = event.notification;
    const action = event.action;
    const data = notification.data || {};

    notification.close();

    if (action === 'dismiss') {
        // Just close the notification
        return;
    }

    // Default action or 'view' action
    let url = data.url || '/';
    
    // Add notification tracking parameters
    if (data.notification_id) {
        url += (url.includes('?') ? '&' : '?') + 'notification_id=' + data.notification_id;
    }

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(function(clientList) {
            // Check if site is already open
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes(url.split('?')[0]) && 'focus' in client) {
                    return client.focus().then(function() {
                        return client.postMessage({
                            type: 'notification-clicked',
                            data: data
                        });
                    });
                }
            }
            
            // Open new window if not already open
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

// Notification close event
self.addEventListener('notificationclose', function(event) {
    console.log('Notification closed:', event.notification);
    
    const data = event.notification.data || {};
    
    // Track notification dismissal
    if (data.notification_id) {
        trackNotificationEvent('dismissed', data.notification_id);
    }
});

// Background sync for offline actions
self.addEventListener('sync', function(event) {
    console.log('Background sync:', event.tag);
    
    if (event.tag === 'notification-read') {
        event.waitUntil(syncNotificationReads());
    } else if (event.tag === 'message-send') {
        event.waitUntil(syncPendingMessages());
    }
});

// Message event - handle messages from main thread
self.addEventListener('message', function(event) {
    console.log('Service Worker received message:', event.data);
    
    const data = event.data;
    
    switch (data.type) {
        case 'skip-waiting':
            self.skipWaiting();
            break;
        case 'cache-notification-read':
            cacheNotificationRead(data.notificationId);
            break;
        case 'cache-message':
            cachePendingMessage(data.message);
            break;
    }
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', function(event) {
    // Only handle same-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // For notification-related requests, try network first
    if (event.request.url.includes('wp-admin/admin-ajax.php') && 
        event.request.url.includes('en_')) {
        
        event.respondWith(
            fetch(event.request).catch(function() {
                // If network fails, try to handle offline
                return handleOfflineNotificationRequest(event.request);
            })
        );
        return;
    }

    // For static assets, use cache first
    if (event.request.url.includes('/environmental-notifications/assets/')) {
        event.respondWith(
            caches.match(event.request).then(function(response) {
                return response || fetch(event.request);
            })
        );
        return;
    }
});

/**
 * Sync notification read status when back online
 */
function syncNotificationReads() {
    return getStoredData('pending-reads').then(function(pendingReads) {
        if (!pendingReads || pendingReads.length === 0) {
            return Promise.resolve();
        }

        const promises = pendingReads.map(function(notificationId) {
            return fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'en_mark_notification_read',
                    notification_id: notificationId,
                    nonce: getStoredNonce()
                })
            });
        });

        return Promise.all(promises).then(function() {
            return clearStoredData('pending-reads');
        });
    });
}

/**
 * Sync pending messages when back online
 */
function syncPendingMessages() {
    return getStoredData('pending-messages').then(function(pendingMessages) {
        if (!pendingMessages || pendingMessages.length === 0) {
            return Promise.resolve();
        }

        const promises = pendingMessages.map(function(message) {
            return fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'en_send_message',
                    content: message.content,
                    recipient_id: message.recipient_id,
                    nonce: getStoredNonce()
                })
            });
        });

        return Promise.all(promises).then(function() {
            return clearStoredData('pending-messages');
        });
    });
}

/**
 * Handle offline notification requests
 */
function handleOfflineNotificationRequest(request) {
    const url = new URL(request.url);
    const action = url.searchParams.get('action');

    switch (action) {
        case 'en_mark_notification_read':
            const notificationId = url.searchParams.get('notification_id');
            cacheNotificationRead(notificationId);
            return new Response(JSON.stringify({
                success: true,
                message: 'Cached for sync when online'
            }), {
                headers: { 'Content-Type': 'application/json' }
            });

        case 'en_send_message':
            // Handle offline message sending
            return new Response(JSON.stringify({
                success: false,
                message: 'Cannot send messages while offline'
            }), {
                headers: { 'Content-Type': 'application/json' }
            });

        default:
            return new Response(JSON.stringify({
                success: false,
                message: 'Offline'
            }), {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            });
    }
}

/**
 * Cache notification read for later sync
 */
function cacheNotificationRead(notificationId) {
    getStoredData('pending-reads').then(function(pendingReads) {
        pendingReads = pendingReads || [];
        if (!pendingReads.includes(notificationId)) {
            pendingReads.push(notificationId);
            storeData('pending-reads', pendingReads);
        }
    });

    // Register for background sync
    self.registration.sync.register('notification-read');
}

/**
 * Cache pending message for later sync
 */
function cachePendingMessage(message) {
    getStoredData('pending-messages').then(function(pendingMessages) {
        pendingMessages = pendingMessages || [];
        pendingMessages.push(message);
        storeData('pending-messages', pendingMessages);
    });

    // Register for background sync
    self.registration.sync.register('message-send');
}

/**
 * Track notification events
 */
function trackNotificationEvent(eventType, notificationId) {
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'en_track_notification_event',
            event_type: eventType,
            notification_id: notificationId,
            nonce: getStoredNonce()
        })
    }).catch(function(error) {
        console.log('Failed to track notification event:', error);
    });
}

/**
 * Storage helpers
 */
function storeData(key, data) {
    return new Promise(function(resolve, reject) {
        const request = indexedDB.open('environmental-notifications', 1);
        
        request.onerror = function() {
            reject(request.error);
        };
        
        request.onsuccess = function() {
            const db = request.result;
            const transaction = db.transaction(['data'], 'readwrite');
            const store = transaction.objectStore('data');
            store.put({ key: key, value: data });
            
            transaction.oncomplete = function() {
                resolve();
            };
        };
        
        request.onupgradeneeded = function() {
            const db = request.result;
            if (!db.objectStoreNames.contains('data')) {
                db.createObjectStore('data', { keyPath: 'key' });
            }
        };
    });
}

function getStoredData(key) {
    return new Promise(function(resolve, reject) {
        const request = indexedDB.open('environmental-notifications', 1);
        
        request.onerror = function() {
            resolve(null);
        };
        
        request.onsuccess = function() {
            const db = request.result;
            const transaction = db.transaction(['data'], 'readonly');
            const store = transaction.objectStore('data');
            const getRequest = store.get(key);
            
            getRequest.onsuccess = function() {
                const result = getRequest.result;
                resolve(result ? result.value : null);
            };
        };
        
        request.onupgradeneeded = function() {
            const db = request.result;
            if (!db.objectStoreNames.contains('data')) {
                db.createObjectStore('data', { keyPath: 'key' });
            }
        };
    });
}

function clearStoredData(key) {
    return storeData(key, null);
}

function getStoredNonce() {
    // This would need to be set by the main application
    return localStorage.getItem('en_nonce') || '';
}
