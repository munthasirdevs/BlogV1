import { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { cn } from '@/utils';
import { Button } from '@/components/atoms';
import { ThemeToggle } from '@/components/organisms/ThemeToggle';
import { Menu, PenSquare, Search } from 'lucide-react';
import { useAuth } from '@/contexts/AuthContext';
import { ROUTES } from '@/constants';
import { useScroll, useIsMd } from '@/hooks';
import SearchBar from '@/components/molecules/SearchBar';
import UserMenu from '@/components/molecules/UserMenu';
import MobileMenu from '@/components/organisms/MobileMenu';

/**
 * Enhanced Header component with navigation, search, user menu, and mobile support
 */
function Header({ className, ...props }) {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const { isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const { isScrolled } = useScroll({ scrollThreshold: 10 });
  const isDesktop = useIsMd();

  const navLinks = [
    { label: 'Home', href: ROUTES.HOME },
    { label: 'Posts', href: ROUTES.POSTS },
    { label: 'Categories', href: ROUTES.CATEGORIES },
    { label: 'Tags', href: ROUTES.TAGS },
    { label: 'About', href: ROUTES.ABOUT },
  ];

  // Check if link is active
  const isActive = (href) => {
    if (href === ROUTES.HOME) {
      return location.pathname === '/';
    }
    return location.pathname.startsWith(href);
  };

  return (
    <>
      <header
        className={cn(
          'sticky top-0 z-40 w-full transition-all duration-300',
          'bg-white/80 dark:bg-secondary-900/80 backdrop-blur-md',
          isScrolled && 'shadow-md bg-white/95 dark:bg-secondary-900/95',
          'border-b border-secondary-200 dark:border-secondary-800',
          className
        )}
        {...props}
      >
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            {/* Logo */}
            <Link
              to={ROUTES.HOME}
              className="flex items-center gap-2.5 group"
              aria-label="Blog Home"
            >
              <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-sm group-hover:shadow-md transition-all">
                <PenSquare className="w-5 h-5 text-white" />
              </div>
              <span className="text-xl font-bold text-secondary-900 dark:text-secondary-100 hidden sm:inline-block">
                Blog
              </span>
            </Link>

            {/* Desktop Navigation */}
            <nav className="hidden md:flex items-center gap-1" aria-label="Main navigation">
              {navLinks.map((link) => (
                <Link
                  key={link.href}
                  to={link.href}
                  className={cn(
                    'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                    isActive(link.href)
                      ? 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20'
                      : 'text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800'
                  )}
                >
                  {link.label}
                </Link>
              ))}
            </nav>

            {/* Right Side Actions */}
            <div className="flex items-center gap-2">
              {/* Search Bar - Desktop */}
              <div className="hidden lg:block w-64 xl:w-80">
                <SearchBar variant="default" placeholder="Search posts..." />
              </div>

              {/* Search Button - Mobile/Tablet */}
              <Button
                variant="ghost"
                size="sm"
                className="lg:hidden p-2"
                onClick={() => navigate(`${ROUTES.POSTS}?q=`)}
                aria-label="Search"
              >
                <Search className="w-5 h-5" />
              </Button>

              {/* Theme Toggle */}
              <ThemeToggle />

              {/* User Menu - Desktop */}
              <div className="hidden md:block">
                <UserMenu variant="default" />
              </div>

              {/* Auth Buttons - Mobile/Tablet */}
              {!isAuthenticated && (
                <div className="md:hidden flex items-center gap-1">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => navigate(ROUTES.LOGIN)}
                    className="text-sm"
                  >
                    Login
                  </Button>
                  <Button
                    size="sm"
                    onClick={() => navigate(ROUTES.REGISTER)}
                    className="text-sm"
                  >
                    Sign Up
                  </Button>
                </div>
              )}

              {/* Mobile Menu Button */}
              <button
                onClick={() => setIsMobileMenuOpen(true)}
                className="md:hidden p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
                aria-label="Open menu"
                aria-expanded={isMobileMenuOpen}
              >
                <Menu className="w-5 h-5 text-secondary-600 dark:text-secondary-400" />
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Mobile Menu */}
      <MobileMenu
        isOpen={isMobileMenuOpen}
        onClose={() => setIsMobileMenuOpen(false)}
      />
    </>
  );
}

export default Header;
