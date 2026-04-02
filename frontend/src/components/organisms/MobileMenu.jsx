import { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { cn } from '@/utils';
import {
  X,
  Menu,
  Home,
  FileText,
  Tags,
  Bookmark,
  Search,
  User,
  Settings,
  Shield,
  LogOut,
  ChevronRight,
} from 'lucide-react';
import { useClickOutside, useScroll } from '@/hooks';
import { useAuth } from '@/contexts/AuthContext';
import { ROUTES } from '@/constants';
import { Button } from '@/components/atoms';
import { ThemeToggle } from '@/components/organisms';
import SearchBar from '@/components/molecules/SearchBar';

/**
 * MobileMenu component with slide-out animation and gesture support
 */
function MobileMenu({ isOpen, onClose, className }) {
  const { user, isAuthenticated, logout } = useAuth();
  const menuRef = useRef(null);
  const [touchStart, setTouchStart] = useState(0);
  const [touchEnd, setTouchEnd] = useState(0);
  const [activeSection, setActiveSection] = useState(null);

  const navigate = useNavigate();

  // Close on outside click
  useClickOutside({
    ref: menuRef,
    handler: onClose,
    enabled: isOpen,
  });

  // Prevent body scroll when menu is open
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isOpen]);

  // Touch gesture handlers for swipe to close
  const handleTouchStart = (e) => {
    setTouchStart(e.targetTouches[0].clientX);
  };

  const handleTouchMove = (e) => {
    setTouchEnd(e.targetTouches[0].clientX);
  };

  const handleTouchEnd = () => {
    // Swipe right to close (if menu is open from left)
    if (touchStart - touchEnd > 100) {
      onClose();
    }
  };

  // Handle logout
  const handleLogout = () => {
    logout();
    navigate(ROUTES.HOME);
    onClose();
  };

  // Navigation links
  const navLinks = [
    { label: 'Home', href: ROUTES.HOME, icon: Home },
    { label: 'Posts', href: ROUTES.POSTS, icon: FileText },
    { label: 'Categories', href: ROUTES.CATEGORIES, icon: Tags },
    { label: 'Tags', href: ROUTES.TAGS, icon: Bookmark },
  ];

  // User menu items
  const userMenuItems = [
    { label: 'Profile', href: ROUTES.PROFILE(user?.username || 'profile'), icon: User },
    { label: 'Bookmarks', href: '/bookmarks', icon: Bookmark },
    { label: 'Settings', href: ROUTES.SETTINGS, icon: Settings },
  ];

  // Get user initials
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
    <>
      {/* Hamburger Button (shown when menu is closed) */}
      {!isOpen && (
        <button
          onClick={onClose}
          className="md:hidden p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
          aria-label="Open menu"
          aria-expanded={isOpen}
        >
          <Menu className="w-5 h-5 text-secondary-600 dark:text-secondary-400" />
        </button>
      )}

      {/* Backdrop */}
      <div
        className={cn(
          'fixed inset-0 bg-black/50 backdrop-blur-sm z-50 md:hidden transition-opacity duration-300',
          isOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'
        )}
        onClick={onClose}
        aria-hidden="true"
      />

      {/* Slide-out Menu */}
      <div
        ref={menuRef}
        className={cn(
          'fixed top-0 left-0 h-full w-80 max-w-[85vw] bg-white dark:bg-secondary-900 z-50 md:hidden',
          'shadow-2xl transform transition-transform duration-300 ease-out',
          isOpen ? 'translate-x-0' : '-translate-x-full',
          className
        )}
        onTouchStart={handleTouchStart}
        onTouchMove={handleTouchMove}
        onTouchEnd={handleTouchEnd}
        role="dialog"
        aria-modal="true"
        aria-label="Mobile navigation menu"
      >
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b border-secondary-200 dark:border-secondary-800">
            <div className="flex items-center gap-3">
              {isAuthenticated && user && (
                <>
                  {user?.avatar ? (
                    <img
                      src={user.avatar}
                      alt={user.name}
                      className="w-10 h-10 rounded-full object-cover"
                    />
                  ) : (
                    <div className="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-medium">
                      {getInitials(user?.name)}
                    </div>
                  )}
                  <div>
                    <p className="text-sm font-semibold text-secondary-900 dark:text-secondary-100">
                      {user?.name || 'User'}
                    </p>
                    <p className="text-xs text-secondary-500 dark:text-secondary-400">
                      {user?.role === 'admin' ? 'Administrator' : 'Member'}
                    </p>
                  </div>
                </>
              )}
              {!isAuthenticated && (
                <span className="text-sm font-semibold text-secondary-900 dark:text-secondary-100">
                  Menu
                </span>
              )}
            </div>
            <button
              onClick={onClose}
              className="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
              aria-label="Close menu"
            >
              <X className="w-5 h-5 text-secondary-600 dark:text-secondary-400" />
            </button>
          </div>

          {/* Search Bar */}
          <div className="p-4 border-b border-secondary-200 dark:border-secondary-800">
            <SearchBar variant="default" placeholder="Search..." />
          </div>

          {/* Navigation Links */}
          <nav className="flex-1 overflow-y-auto py-4">
            <div className="px-4 mb-4">
              <p className="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-2">
                Navigation
              </p>
              <div className="space-y-1">
                {navLinks.map((link) => (
                  <Link
                    key={link.href}
                    to={link.href}
                    onClick={() => {
                      onClose();
                    }}
                    className={cn(
                      'flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium',
                      'text-secondary-700 dark:text-secondary-300',
                      'hover:bg-secondary-100 dark:hover:bg-secondary-800',
                      'transition-colors'
                    )}
                  >
                    <div className="flex items-center gap-3">
                      <link.icon className="w-5 h-5 text-secondary-400" />
                      {link.label}
                    </div>
                    <ChevronRight className="w-4 h-4 text-secondary-400" />
                  </Link>
                ))}
              </div>
            </div>

            {/* User Section */}
            {isAuthenticated ? (
              <div className="px-4 mb-4">
                <p className="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-2">
                  Account
                </p>
                <div className="space-y-1">
                  {userMenuItems.map((item) => (
                    <Link
                      key={item.href}
                      to={item.href}
                      onClick={() => onClose()}
                      className={cn(
                        'flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium',
                        'text-secondary-700 dark:text-secondary-300',
                        'hover:bg-secondary-100 dark:hover:bg-secondary-800',
                        'transition-colors'
                      )}
                    >
                      <div className="flex items-center gap-3">
                        <item.icon className="w-5 h-5 text-secondary-400" />
                        {item.label}
                      </div>
                      <ChevronRight className="w-4 h-4 text-secondary-400" />
                    </Link>
                  ))}
                  {user?.role === 'admin' && (
                    <Link
                      to={ROUTES.ADMIN_DASHBOARD}
                      onClick={() => onClose()}
                      className={cn(
                        'flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium',
                        'text-secondary-700 dark:text-secondary-300',
                        'hover:bg-secondary-100 dark:hover:bg-secondary-800',
                        'transition-colors'
                      )}
                    >
                      <div className="flex items-center gap-3">
                        <Shield className="w-5 h-5 text-secondary-400" />
                        Admin Dashboard
                      </div>
                      <ChevronRight className="w-4 h-4 text-secondary-400" />
                    </Link>
                  )}
                </div>
              </div>
            ) : (
              <div className="px-4 mb-4">
                <p className="text-xs font-semibold text-secondary-500 uppercase tracking-wider mb-2">
                  Get Started
                </p>
                <div className="space-y-2">
                  <Button
                    variant="outline"
                    className="w-full justify-center"
                    onClick={() => {
                      navigate(ROUTES.LOGIN);
                      onClose();
                    }}
                  >
                    Login
                  </Button>
                  <Button
                    className="w-full justify-center"
                    onClick={() => {
                      navigate(ROUTES.REGISTER);
                      onClose();
                    }}
                  >
                    Sign Up
                  </Button>
                </div>
              </div>
            )}
          </nav>

          {/* Footer Actions */}
          <div className="p-4 border-t border-secondary-200 dark:border-secondary-800 space-y-3">
            {/* Theme Toggle */}
            <div className="flex items-center justify-between px-3 py-2 rounded-lg bg-secondary-50 dark:bg-secondary-800">
              <span className="text-sm text-secondary-700 dark:text-secondary-300">Dark Mode</span>
              <ThemeToggle />
            </div>

            {/* Logout */}
            {isAuthenticated && (
              <button
                onClick={handleLogout}
                className={cn(
                  'w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium',
                  'text-danger-600 dark:text-danger-400',
                  'bg-danger-50 dark:bg-danger-900/20',
                  'hover:bg-danger-100 dark:hover:bg-danger-900/30',
                  'transition-colors'
                )}
              >
                <LogOut className="w-5 h-5" />
                Logout
              </button>
            )}
          </div>
        </div>
      </div>
    </>
  );
}

export default MobileMenu;
