# Frontend Integration Guide: Real-time Notifications with Laravel Echo

## Overview

This guide explains how to integrate real-time notifications into your frontend application using Laravel Echo and WebSockets (Reverb/Pusher).

## Prerequisites

1. Laravel backend with Phase 13 Notifications System installed
2. Broadcasting configured (Reverb, Pusher, or other WebSocket server)
3. User authentication token from Laravel Sanctum

## Installation

### Install Dependencies

```bash
# Using npm
npm install laravel-echo pusher-js

# Using yarn
yarn add laravel-echo pusher-js
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Laravel Reverb (recommended)
VITE_REVERB_APP_KEY=your-app-key
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http

# Or Pusher
VITE_PUSHER_APP_KEY=your-pusher-key
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
VITE_PUSHER_APP_CLUSTER=mt1
```

### Echo Setup

Create a new file `src/lib/echo.js` (or similar):

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Echo
window.Pusher = Pusher;

class NotificationService {
  constructor() {
    this.echo = null;
    this.userId = null;
    this.channels = [];
  }

  /**
   * Initialize Laravel Echo with authentication
   */
  init(authToken, userId) {
    this.userId = userId;
    this.authToken = authToken;

    this.echo = new Echo({
      broadcaster: 'reverb', // or 'pusher'
      key: import.meta.env.VITE_REVERB_APP_KEY,
      wsHost: import.meta.env.VITE_REVERB_HOST,
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
      encrypted: true,
      
      // Authentication configuration
      auth: {
        headers: {
          Authorization: `Bearer ${this.authToken}`,
          Accept: 'application/json',
        },
      },

      // Handle authentication errors
      authorizer: (channel, options) => {
        return {
          authorize: (socketId, callback) => {
            fetch(`/api/broadcasting/auth`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${this.authToken}`,
              },
              body: JSON.stringify({
                socket_id: socketId,
                channel_name: channel.name,
              }),
            })
              .then(response => response.json())
              .then(data => callback(null, data))
              .catch(error => callback(error, null));
          },
        };
      },
    });

    this.subscribeToChannels();
    return this;
  }

  /**
   * Subscribe to notification channels
   */
  subscribeToChannels() {
    if (!this.userId || !this.echo) return;

    // Subscribe to user-specific notifications channel
    const channel = this.echo.private(`notifications.${this.userId}`);
    
    channel.listen('.notification.created', (event) => {
      this.handleNotification(event);
    });

    // Handle connection events
    channel.subscribed(() => {
      console.log('Connected to notifications channel');
      this.fetchUnreadCount();
    });

    channel.error((error) => {
      console.error('Notification channel error:', error);
    });

    this.channels.push(channel);
  }

  /**
   * Handle incoming notification
   */
  handleNotification(event) {
    console.log('New notification received:', event);

    // Update notification badge
    this.updateBadgeCount();

    // Add to notification list
    this.addToList(event);

    // Show toast/browser notification
    this.showToast(event);

    // Trigger custom event for other components
    window.dispatchEvent(new CustomEvent('notification-received', { 
      detail: event 
    }));
  }

  /**
   * Fetch unread notification count
   */
  async fetchUnreadCount() {
    try {
      const response = await fetch('/api/v1/notifications/unread-count', {
        headers: {
          Authorization: `Bearer ${this.authToken}`,
          Accept: 'application/json',
        },
      });
      const data = await response.json();
      this.updateBadgeCount(data.data.unread_count);
    } catch (error) {
      console.error('Failed to fetch unread count:', error);
    }
  }

  /**
   * Update notification badge
   */
  updateBadgeCount(count = null) {
    if (count === null) {
      // Increment existing count
      const badge = document.querySelector('.notification-badge');
      if (badge) {
        const current = parseInt(badge.textContent) || 0;
        badge.textContent = current + 1;
        badge.style.display = current + 1 > 0 ? 'inline-block' : 'none';
      }
    } else {
      // Set specific count
      const badge = document.querySelector('.notification-badge');
      if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
      }
    }
  }

  /**
   * Add notification to list
   */
  addToList(event) {
    const list = document.querySelector('.notification-list');
    if (!list) return;

    const notification = this.createNotificationElement(event);
    list.insertBefore(notification, list.firstChild);

    // Limit list size
    while (list.children.length > 50) {
      list.removeChild(list.lastChild);
    }
  }

  /**
   * Create notification DOM element
   */
  createNotificationElement(event) {
    const div = document.createElement('div');
    div.className = `notification-item ${!event.is_read ? 'unread' : ''}`;
    div.dataset.id = event.id;

    const icon = this.getIconForType(event.notification_type);
    const color = this.getColorForType(event.notification_type);

    div.innerHTML = `
      <div class="notification-content">
        <span class="notification-icon" style="color: ${color}">${icon}</span>
        <div class="notification-body">
          <p class="notification-title">${event.title}</p>
          <p class="notification-message">${event.message}</p>
          <span class="notification-time">${this.timeAgo(new Date(event.created_at))}</span>
        </div>
        ${event.from_user?.avatar ? 
          `<img src="${event.from_user.avatar}" alt="${event.from_user.name}" class="notification-avatar">` 
          : ''}
      </div>
      <a href="${event.action_url}" class="notification-action">View</a>
    `;

    // Mark as read on click
    div.addEventListener('click', (e) => {
      if (!e.target.closest('.notification-action')) {
        this.markAsRead(event.id);
      }
    });

    return div;
  }

  /**
   * Show toast notification
   */
  showToast(event) {
    // Using browser notification API
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(event.title, {
        body: event.message,
        icon: event.from_user?.avatar || '/favicon.ico',
        badge: '/favicon.ico',
        tag: event.id,
        requireInteraction: false,
      });
    }

    // Or custom toast
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = `
      <div class="toast-content">
        <strong>${event.title}</strong>
        <p>${event.message}</p>
      </div>
      <button onclick="this.parentElement.remove()">×</button>
    `;

    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
  }

  /**
   * Mark notification as read
   */
  async markAsRead(notificationId) {
    try {
      await fetch(`/api/v1/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${this.authToken}`,
          Accept: 'application/json',
        },
      });

      // Update UI
      const element = document.querySelector(`[data-id="${notificationId}"]`);
      if (element) {
        element.classList.remove('unread');
      }

      this.updateBadgeCount();
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    }
  }

  /**
   * Mark all notifications as read
   */
  async markAllAsRead() {
    try {
      await fetch('/api/v1/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${this.authToken}`,
          Accept: 'application/json',
        },
      });

      // Update UI
      document.querySelectorAll('.notification-item.unread').forEach(el => {
        el.classList.remove('unread');
      });

      this.updateBadgeCount(0);
    } catch (error) {
      console.error('Failed to mark all as read:', error);
    }
  }

  /**
   * Get icon for notification type
   */
  getIconForType(type) {
    const icons = {
      'new_comment': '💬',
      'new_reply': '↩️',
      'new_like_post': '❤️',
      'new_like_comment': '❤️',
      'mention': '@',
      'post_published': '📝',
    };
    return icons[type] || '🔔';
  }

  /**
   * Get color for notification type
   */
  getColorForType(type) {
    const colors = {
      'new_comment': '#3b82f6',
      'new_reply': '#3b82f6',
      'new_like_post': '#ef4444',
      'new_like_comment': '#ef4444',
      'mention': '#a855f7',
      'post_published': '#22c55e',
    };
    return colors[type] || '#6b7280';
  }

  /**
   * Format time ago
   */
  timeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    const intervals = {
      year: 31536000,
      month: 2592000,
      week: 604800,
      day: 86400,
      hour: 3600,
      minute: 60,
    };

    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
      const interval = Math.floor(seconds / secondsInUnit);
      if (interval >= 1) {
        return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
      }
    }
    return 'Just now';
  }

  /**
   * Disconnect from all channels
   */
  disconnect() {
    if (this.echo) {
      this.channels.forEach(channel => channel.stopListening('.notification.created'));
      this.echo.disconnect();
      this.echo = null;
      this.channels = [];
    }
  }
}

// Export singleton instance
export const notificationService = new NotificationService();
export default notificationService;
```

## React Integration

### NotificationProvider Component

```jsx
// src/contexts/NotificationContext.jsx
import React, { createContext, useContext, useEffect, useState } from 'react';
import notificationService from '../lib/echo';

const NotificationContext = createContext(null);

export function NotificationProvider({ children, authToken, userId }) {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isConnected, setIsConnected] = useState(false);

  useEffect(() => {
    // Initialize Echo
    notificationService.init(authToken, userId);

    // Listen for custom events
    const handleNotification = (event) => {
      setNotifications(prev => [event.detail, ...prev].slice(0, 50));
      setUnreadCount(prev => prev + 1);
      setIsConnected(true);
    };

    window.addEventListener('notification-received', handleNotification);

    // Fetch initial notifications
    fetchInitialNotifications();

    return () => {
      window.removeEventListener('notification-received', handleNotification);
      notificationService.disconnect();
    };
  }, [authToken, userId]);

  const fetchInitialNotifications = async () => {
    try {
      const [notificationsRes, countRes] = await Promise.all([
        fetch('/api/v1/notifications?per_page=20', {
          headers: { Authorization: `Bearer ${authToken}` },
        }),
        fetch('/api/v1/notifications/unread-count', {
          headers: { Authorization: `Bearer ${authToken}` },
        }),
      ]);

      const notificationsData = await notificationsRes.json();
      const countData = await countRes.json();

      setNotifications(notificationsData.data || []);
      setUnreadCount(countData.data.unread_count);
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    }
  };

  const markAsRead = async (id) => {
    await notificationService.markAsRead(id);
    setNotifications(prev => 
      prev.map(n => n.id === id ? { ...n, is_read: true } : n)
    );
    setUnreadCount(prev => Math.max(0, prev - 1));
  };

  const markAllAsRead = async () => {
    await notificationService.markAllAsRead();
    setNotifications(prev => prev.map(n => ({ ...n, is_read: true })));
    setUnreadCount(0);
  };

  return (
    <NotificationContext.Provider value={{
      notifications,
      unreadCount,
      isConnected,
      markAsRead,
      markAllAsRead,
    }}>
      {children}
    </NotificationContext.Provider>
  );
}

export const useNotifications = () => useContext(NotificationContext);
```

### Usage in App

```jsx
// src/App.jsx
import { NotificationProvider } from './contexts/NotificationContext';

function App() {
  const authToken = useAuth().token;
  const userId = useAuth().user.id;

  return (
    <NotificationProvider authToken={authToken} userId={userId}>
      <YourApp />
    </NotificationProvider>
  );
}
```

### NotificationBell Component

```jsx
// src/components/NotificationBell.jsx
import { useNotifications } from '../contexts/NotificationContext';

export function NotificationBell() {
  const { unreadCount, markAllAsRead } = useNotifications();

  return (
    <div className="notification-bell">
      <button className="bell-button">
        🔔
        {unreadCount > 0 && (
          <span className="notification-badge">{unreadCount}</span>
        )}
      </button>
      {unreadCount > 0 && (
        <button onClick={markAllAsRead} className="mark-all-read">
          Mark all read
        </button>
      )}
    </div>
  );
}
```

## Vue Integration

### Composable

```javascript
// src/composables/useNotifications.js
import { ref, onMounted, onUnmounted } from 'vue';
import notificationService from '../lib/echo';

export function useNotifications(authToken, userId) {
  const notifications = ref([]);
  const unreadCount = ref(0);
  const isConnected = ref(false);

  onMounted(() => {
    notificationService.init(authToken, userId);

    const handleNotification = (event) => {
      notifications.value.unshift(event.detail);
      unreadCount.value++;
      isConnected.value = true;
    };

    window.addEventListener('notification-received', handleNotification);
    fetchInitialNotifications();

    onUnmounted(() => {
      window.removeEventListener('notification-received', handleNotification);
      notificationService.disconnect();
    });
  });

  const fetchInitialNotifications = async () => {
    // Fetch initial data
  };

  const markAsRead = async (id) => {
    await notificationService.markAsRead(id);
    unreadCount.value--;
  };

  return {
    notifications,
    unreadCount,
    isConnected,
    markAsRead,
  };
}
```

## Requesting Browser Notification Permission

```javascript
// Request permission on app load
export async function requestNotificationPermission() {
  if (!('Notification' in window)) {
    console.log('Browser does not support notifications');
    return false;
  }

  if (Notification.permission === 'granted') {
    return true;
  }

  if (Notification.permission !== 'denied') {
    const permission = await Notification.requestPermission();
    return permission === 'granted';
  }

  return false;
}
```

## Testing

### Test Connection

```javascript
// In browser console
import { notificationService } from './lib/echo';

// Check connection
console.log(notificationService.echo);

// Send test notification (requires dev environment)
fetch('/api/v1/notifications/test', {
  method: 'POST',
  headers: { Authorization: `Bearer ${token}` },
});
```

## Troubleshooting

### Connection Issues

1. Check WebSocket server is running
2. Verify auth token is valid
3. Check CORS configuration
4. Inspect browser console for errors

### Authentication Errors

1. Ensure Sanctum token is valid
2. Check broadcasting auth route is configured
3. Verify middleware allows the request

### Notifications Not Showing

1. Check user preferences are enabled
2. Verify broadcast channel configuration
3. Check event is being fired on backend

## Best Practices

1. **Reconnection**: Implement automatic reconnection logic
2. **Error Handling**: Handle connection errors gracefully
3. **Performance**: Limit stored notifications in memory
4. **Privacy**: Don't show sensitive info in browser notifications
5. **Battery**: Disconnect when tab is hidden/inactive
