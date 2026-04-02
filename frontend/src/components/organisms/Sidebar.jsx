import { useState, useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { cn } from '@/utils';
import {
  LayoutDashboard,
  FileText,
  Users,
  Tags,
  Settings,
  LogOut,
  Home,
  ChevronLeft,
  ChevronRight,
  MessageSquare,
  Image,
  BarChart3,
} from 'lucide-react';
import { ROUTES } from '@/constants';
import { useAuth } from '@/contexts/AuthContext';
import { useLocalStorage, useIsLg } from '@/hooks';

/**
 * Enhanced Sidebar component for admin dashboard with collapsible functionality
 */
function Sidebar({ 
  className, 
  collapsible = true, 
  defaultCollapsed = false,
  onCollapse,
  onClose,
}) {
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const isDesktop = useIsLg();

  // Collapse state with localStorage persistence
  const [isCollapsed, setIsCollapsed] = useLocalStorage(
    'sidebar_collapsed',
    defaultCollapsed
  );

  // Auto-collapse on mobile
  useEffect(() => {
    if (!isDesktop) {
      setIsCollapsed(true);
    }
  }, [isDesktop, setIsCollapsed]);

  // Notify parent of collapse state changes
  useEffect(() => {
    onCollapse?.(isCollapsed);
  }, [isCollapsed, onCollapse]);

  // Check if user is admin
  const isAdmin = user?.role === 'admin';
  const isEditor = user?.role === 'editor' || isAdmin;

  const navItems = [
    {
      label: 'Back to Site',
      href: ROUTES.HOME,
      icon: Home,
      external: true,
      roles: ['admin', 'editor', 'author'],
    },
    {
      label: 'Dashboard',
      href: ROUTES.ADMIN_DASHBOARD,
      icon: LayoutDashboard,
      badge: null,
      roles: ['admin', 'editor', 'author'],
    },
    {
      label: 'Posts',
      href: ROUTES.ADMIN_POSTS,
      icon: FileText,
      badge: null,
      roles: ['admin', 'editor', 'author'],
    },
    {
      label: 'Media',
      href: '/admin/media',
      icon: Image,
      badge: null,
      roles: ['admin', 'editor', 'author'],
    },
    {
      label: 'Comments',
      href: '/admin/comments',
      icon: MessageSquare,
      badge: null,
      roles: ['admin', 'editor'],
    },
    {
      label: 'Analytics',
      href: '/admin/analytics',
      icon: BarChart3,
      badge: null,
      roles: ['admin', 'editor'],
    },
    {
      label: 'Users',
      href: ROUTES.ADMIN_USERS,
      icon: Users,
      badge: null,
      roles: ['admin'],
    },
    {
      label: 'Categories',
      href: ROUTES.ADMIN_CATEGORIES,
      icon: Tags,
      badge: null,
      roles: ['admin', 'editor'],
    },
    {
      label: 'Settings',
      href: ROUTES.ADMIN_SETTINGS,
      icon: Settings,
      badge: null,
      roles: ['admin'],
    },
  ];

  // Filter nav items based on user role
  const filteredNavItems = navItems.filter((item) => {
    if (!item.roles) return true;
    return item.roles.includes(user?.role);
  });

  const isActive = (href) => {
    if (href === ROUTES.HOME) return false;
    return location.pathname.startsWith(href);
  };

  const handleLogout = () => {
    logout();
    navigate(ROUTES.HOME);
  };

  const toggleCollapse = () => {
    setIsCollapsed(!isCollapsed);
  };

  return (
    <aside
      className={cn(
        'flex flex-col h-screen sticky top-0',
        'bg-white dark:bg-secondary-900',
        'border-r border-secondary-200 dark:border-secondary-800',
        'transition-all duration-300 ease-in-out',
        isCollapsed ? 'w-20' : 'w-64',
        className
      )}
    >
      {/* Logo Section */}
      <div
        className={cn(
          'flex items-center h-16 px-4 border-b border-secondary-200 dark:border-secondary-800',
          isCollapsed ? 'justify-center' : 'justify-between'
        )}
      >
        {!isCollapsed && (
          <Link
            to={ROUTES.ADMIN_DASHBOARD}
            className="text-lg font-bold text-secondary-900 dark:text-secondary-100"
          >
            Admin Panel
          </Link>
        )}
        {isCollapsed && (
          <Link
            to={ROUTES.ADMIN_DASHBOARD}
            className="w-9 h-9 rounded-lg bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center"
            aria-label="Admin Dashboard"
          >
            <LayoutDashboard className="w-5 h-5 text-white" />
          </Link>
        )}
        {collapsible && isDesktop && (
          <button
            onClick={toggleCollapse}
            className="p-1.5 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
            aria-label={isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            {isCollapsed ? (
              <ChevronRight className="w-4 h-4 text-secondary-500" />
            ) : (
              <ChevronLeft className="w-4 h-4 text-secondary-500" />
            )}
          </button>
        )}
      </div>

      {/* Navigation */}
      <nav className="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        {filteredNavItems.map((item) => (
          <Link
            key={item.href}
            to={item.href}
            onClick={() => onClose?.()}
            className={cn(
              'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all',
              isActive(item.href)
                ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400'
                : 'text-secondary-600 dark:text-secondary-400 hover:bg-secondary-100 dark:hover:bg-secondary-800',
              isCollapsed && 'justify-center'
            )}
            title={isCollapsed ? item.label : undefined}
          >
            <item.icon
              className={cn(
                'w-5 h-5 flex-shrink-0',
                isActive(item.href) && 'text-primary-600 dark:text-primary-400'
              )}
            />
            {!isCollapsed && (
              <>
                <span className="flex-1">{item.label}</span>
                {item.badge && (
                  <span className="px-2 py-0.5 text-xs font-medium text-white bg-primary-600 rounded-full">
                    {item.badge}
                  </span>
                )}
              </>
            )}
            {isCollapsed && item.badge && (
              <span className="absolute top-2 right-2 w-2 h-2 bg-primary-600 rounded-full" />
            )}
          </Link>
        ))}
      </nav>

      {/* Logout Section */}
      <div
        className={cn(
          'p-3 border-t border-secondary-200 dark:border-secondary-800',
          isCollapsed && 'flex justify-center'
        )}
      >
        <button
          onClick={handleLogout}
          className={cn(
            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium w-full',
            'text-danger-600 dark:text-danger-400',
            'hover:bg-danger-50 dark:hover:bg-danger-900/20',
            'transition-colors',
            isCollapsed && 'justify-center'
          )}
          title={isCollapsed ? 'Logout' : undefined}
        >
          <LogOut className="w-5 h-5 flex-shrink-0" />
          {!isCollapsed && <span>Logout</span>}
        </button>
      </div>

      {/* Collapse Hint - Desktop Only */}
      {collapsible && isDesktop && !isCollapsed && (
        <div className="absolute bottom-4 right-4 text-xs text-secondary-400 pointer-events-none">
          Press to collapse
        </div>
      )}
    </aside>
  );
}

export default Sidebar;
