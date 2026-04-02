import { useState, useRef } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { cn } from '@/utils';
import {
  User,
  Settings,
  Bookmark,
  Shield,
  LogOut,
  ChevronDown,
} from 'lucide-react';
import { useClickOutside } from '@/hooks';
import { useAuth } from '@/contexts/AuthContext';
import { useToast } from '@/contexts/ToastContext';
import { ROUTES } from '@/constants';
import { Button } from '@/components/atoms';

/**
 * UserMenu dropdown component with profile options
 */
function UserMenu({ className, variant = 'default' }) {
  const [isOpen, setIsOpen] = useState(false);
  const { user, isAuthenticated, logout } = useAuth();
  const toast = useToast();
  const navigate = useNavigate();
  const menuRef = useRef(null);

  // Close menu on outside click
  useClickOutside({
    ref: menuRef,
    handler: () => setIsOpen(false),
  });

  // Handle logout
  const handleLogout = async () => {
    try {
      await logout();
      toast.success('Logged Out', 'You have been successfully logged out.');
      navigate(ROUTES.HOME);
      setIsOpen(false);
    } catch (error) {
      toast.error('Logout Failed', 'There was an issue logging you out. Please try again.');
    }
  };

  // Handle keyboard navigation
  const handleKeyDown = (e) => {
    if (e.key === 'Escape') {
      setIsOpen(false);
    }
  };

  const menuItems = [
    {
      label: 'Profile',
      href: ROUTES.PROFILE(user?.username || 'profile'),
      icon: User,
      show: true,
    },
    {
      label: 'Bookmarks',
      href: '/bookmarks',
      icon: Bookmark,
      show: true,
    },
    {
      label: 'Settings',
      href: ROUTES.SETTINGS,
      icon: Settings,
      show: true,
    },
    {
      label: 'Admin Dashboard',
      href: ROUTES.ADMIN_DASHBOARD,
      icon: Shield,
      show: user?.role === 'admin',
    },
  ];

  // Get user initials for avatar
  const getInitials = (name) => {
    if (!name) return 'U';
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <div ref={menuRef} className={cn('relative', className)} onKeyDown={handleKeyDown}>
      {/* Trigger Button */}
      {isAuthenticated ? (
        <button
          onClick={() => setIsOpen(!isOpen)}
          className={cn(
            'flex items-center gap-2 rounded-lg transition-colors',
            variant === 'default' &&
              'p-1.5 hover:bg-secondary-100 dark:hover:bg-secondary-800',
            variant === 'compact' && 'p-0'
          )}
          aria-label="User menu"
          aria-expanded={isOpen}
          aria-haspopup="menu"
        >
          {user?.avatar ? (
            <img
              src={user.avatar}
              alt={user?.name || 'User'}
              className="w-8 h-8 rounded-full object-cover ring-2 ring-secondary-200 dark:ring-secondary-700"
            />
          ) : (
            <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-sm text-white font-medium ring-2 ring-secondary-200 dark:ring-secondary-700">
              {getInitials(user?.name)}
            </div>
          )}
          {variant === 'default' && (
            <ChevronDown
              className={cn(
                'w-4 h-4 text-secondary-500 transition-transform',
                isOpen && 'rotate-180'
              )}
            />
          )}
        </button>
      ) : (
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate(ROUTES.LOGIN)}
            className="hidden sm:inline-flex"
          >
            Login
          </Button>
          <Button
            size="sm"
            onClick={() => navigate(ROUTES.REGISTER)}
            className="hidden sm:inline-flex"
          >
            Sign Up
          </Button>
        </div>
      )}

      {/* Dropdown Menu */}
      {isOpen && isAuthenticated && (
        <>
          {/* Backdrop for mobile */}
          <div className="fixed inset-0 z-40 lg:hidden" onClick={() => setIsOpen(false)} />

          {/* Menu Content */}
          <div
            className={cn(
              'absolute right-0 mt-2 w-56 py-2 bg-white dark:bg-secondary-800 rounded-lg shadow-lg',
              'border border-secondary-200 dark:border-secondary-700 z-50',
              'animate-in fade-in slide-in-from-top-2 duration-200'
            )}
            role="menu"
            aria-orientation="vertical"
            aria-labelledby="user-menu"
          >
            {/* User Info Header */}
            <div className="px-4 py-3 border-b border-secondary-200 dark:border-secondary-700">
              <p className="text-sm font-medium text-secondary-900 dark:text-secondary-100">
                {user?.name || 'User'}
              </p>
              <p className="text-xs text-secondary-500 dark:text-secondary-400 truncate">
                {user?.email || 'user@example.com'}
              </p>
            </div>

            {/* Menu Items */}
            <div className="py-1">
              {menuItems
                .filter((item) => item.show)
                .map((item) => (
                  <Link
                    key={item.label}
                    to={item.href}
                    onClick={() => setIsOpen(false)}
                    className={cn(
                      'flex items-center gap-3 px-4 py-2.5 text-sm',
                      'text-secondary-700 dark:text-secondary-300',
                      'hover:bg-secondary-100 dark:hover:bg-secondary-700',
                      'transition-colors'
                    )}
                    role="menuitem"
                  >
                    <item.icon className="w-4 h-4 text-secondary-400" />
                    {item.label}
                  </Link>
                ))}
            </div>

            {/* Logout */}
            <div className="py-1 border-t border-secondary-200 dark:border-secondary-700">
              <button
                onClick={handleLogout}
                className={cn(
                  'w-full flex items-center gap-3 px-4 py-2.5 text-sm',
                  'text-danger-600 dark:text-danger-400',
                  'hover:bg-danger-50 dark:hover:bg-danger-900/20',
                  'transition-colors'
                )}
                role="menuitem"
              >
                <LogOut className="w-4 h-4" />
                Logout
              </button>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

export default UserMenu;
