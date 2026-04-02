import { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Bell,
  Search,
  Menu,
  X,
  User,
  Settings,
  LogOut,
  ChevronDown,
  Moon,
  Sun,
  Home,
} from 'lucide-react';
import { cn } from '@/utils';
import { useAuth } from '@/contexts/AuthContext';
import { useTheme } from '@/contexts/ThemeContext';
import { ROUTES } from '@/constants';
import { useClickOutside } from '@/hooks';

/**
 * Dashboard header component with user menu and notifications
 */
function DashboardHeader({ onMenuClick, className, ...props }) {
  const { user, logout } = useAuth();
  const { theme, toggleTheme } = useTheme();
  const [showUserMenu, setShowUserMenu] = useState(false);
  const [showNotifications, setShowNotifications] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const userMenuRef = useClickOutside(() => setShowUserMenu(false));
  const notificationsRef = useClickOutside(() => setShowNotifications(false));

  const handleLogout = () => {
    logout();
  };

  const userInitials = user?.name
    ? user.name.split(' ').map((n) => n[0]).join('').toUpperCase()
    : user?.email?.[0].toUpperCase() || 'U';

  // Mock notifications - will be replaced with real API data
  const notifications = [
    { id: 1, title: 'New comment on your post', time: '5 min ago', unread: true },
    { id: 2, title: 'Post "React Tips" was published', time: '1 hour ago', unread: true },
    { id: 3, title: 'User John requested to follow you', time: '2 hours ago', unread: false },
  ];

  const unreadCount = notifications.filter((n) => n.unread).length;

  return (
    <header
      className={cn(
        'sticky top-0 z-40',
        'bg-white dark:bg-secondary-900',
        'border-b border-secondary-200 dark:border-secondary-800',
        className
      )}
      {...props}
    >
      <div className="flex items-center justify-between h-16 px-4 lg:px-6">
        {/* Left section - Menu toggle and Search */}
        <div className="flex items-center gap-4 flex-1">
          <button
            onClick={onMenuClick}
            className="lg:hidden p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
            aria-label="Toggle menu"
          >
            <Menu className="w-5 h-5 text-secondary-500" />
          </button>

          <div className="relative hidden md:block max-w-md flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-secondary-400" />
            <input
              type="text"
              placeholder="Search posts, users, categories..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className={cn(
                'w-full pl-10 pr-4 py-2 rounded-lg',
                'bg-secondary-100 dark:bg-secondary-800',
                'border border-transparent focus:border-primary-500',
                'text-sm text-secondary-900 dark:text-secondary-100',
                'placeholder:text-secondary-400',
                'focus:outline-none focus:ring-2 focus:ring-primary-500/20'
              )}
            />
          </div>
        </div>

        {/* Right section - Actions and User menu */}
        <div className="flex items-center gap-2 lg:gap-4">
          {/* Back to Site */}
          <Link
            to={ROUTES.HOME}
            className="hidden sm:flex items-center gap-2 px-3 py-2 text-sm font-medium text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800 rounded-lg transition-colors"
          >
            <Home className="w-4 h-4" />
            <span className="hidden lg:inline">Back to Site</span>
          </Link>

          {/* Theme Toggle */}
          <button
            onClick={toggleTheme}
            className="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
            aria-label={`Switch to ${theme === 'dark' ? 'light' : 'dark'} mode`}
          >
            {theme === 'dark' ? (
              <Sun className="w-5 h-5 text-secondary-500" />
            ) : (
              <Moon className="w-5 h-5 text-secondary-500" />
            )}
          </button>

          {/* Notifications */}
          <div className="relative" ref={notificationsRef}>
            <button
              onClick={() => setShowNotifications(!showNotifications)}
              className="relative p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
              aria-label="Notifications"
            >
              <Bell className="w-5 h-5 text-secondary-500" />
              {unreadCount > 0 && (
                <span className="absolute top-1 right-1 w-4 h-4 bg-danger-500 text-white text-xs rounded-full flex items-center justify-center">
                  {unreadCount}
                </span>
              )}
            </button>

            {showNotifications && (
              <div className="absolute right-0 mt-2 w-80 bg-white dark:bg-secondary-900 rounded-xl shadow-lg border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                <div className="flex items-center justify-between px-4 py-3 border-b border-secondary-200 dark:border-secondary-800">
                  <h3 className="font-semibold text-secondary-900 dark:text-secondary-100">
                    Notifications
                  </h3>
                  <button className="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                    Mark all read
                  </button>
                </div>
                <div className="max-h-96 overflow-y-auto">
                  {notifications.map((notification) => (
                    <div
                      key={notification.id}
                      className={cn(
                        'px-4 py-3 border-b border-secondary-100 dark:border-secondary-800 last:border-0',
                        'hover:bg-secondary-50 dark:hover:bg-secondary-800 cursor-pointer',
                        notification.unread && 'bg-primary-50/50 dark:bg-primary-900/10'
                      )}
                    >
                      <p className="text-sm text-secondary-900 dark:text-secondary-100">
                        {notification.title}
                      </p>
                      <p className="text-xs text-secondary-500 mt-1">{notification.time}</p>
                    </div>
                  ))}
                </div>
                <div className="px-4 py-3 text-center border-t border-secondary-200 dark:border-secondary-800">
                  <button className="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                    View all notifications
                  </button>
                </div>
              </div>
            )}
          </div>

          {/* User Menu */}
          <div className="relative" ref={userMenuRef}>
            <button
              onClick={() => setShowUserMenu(!showUserMenu)}
              className="flex items-center gap-2 p-1.5 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
              aria-label="User menu"
            >
              <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-sm font-medium">
                {userInitials}
              </div>
              <ChevronDown className="w-4 h-4 text-secondary-500 hidden lg:block" />
            </button>

            {showUserMenu && (
              <div className="absolute right-0 mt-2 w-56 bg-white dark:bg-secondary-900 rounded-xl shadow-lg border border-secondary-200 dark:border-secondary-800 overflow-hidden">
                {/* User Info */}
                <div className="px-4 py-3 border-b border-secondary-200 dark:border-secondary-800">
                  <p className="font-medium text-secondary-900 dark:text-secondary-100">
                    {user?.name || 'User'}
                  </p>
                  <p className="text-sm text-secondary-500 truncate">{user?.email}</p>
                  <p className="text-xs text-primary-600 dark:text-primary-400 mt-1 capitalize">
                    {user?.role || 'user'}
                  </p>
                </div>

                {/* Menu Items */}
                <div className="py-2">
                  <Link
                    to={ROUTES.PROFILE(user?.username || 'profile')}
                    className="flex items-center gap-3 px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800"
                  >
                    <User className="w-4 h-4" />
                    Profile
                  </Link>
                  <Link
                    to={ROUTES.SETTINGS}
                    className="flex items-center gap-3 px-4 py-2 text-sm text-secondary-700 dark:text-secondary-300 hover:bg-secondary-100 dark:hover:bg-secondary-800"
                  >
                    <Settings className="w-4 h-4" />
                    Settings
                  </Link>
                </div>

                {/* Logout */}
                <div className="py-2 border-t border-secondary-200 dark:border-secondary-800">
                  <button
                    onClick={handleLogout}
                    className="flex items-center gap-3 w-full px-4 py-2 text-sm text-danger-600 dark:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-900/20"
                  >
                    <LogOut className="w-4 h-4" />
                    Logout
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
}

export default DashboardHeader;
