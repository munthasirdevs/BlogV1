import { Link, useLocation } from 'react-router-dom';
import { cn } from '@/utils';
import { ChevronRight, Home } from 'lucide-react';
import { ROUTES } from '@/constants';

/**
 * Breadcrumb component for nested pages
 * Shows current location hierarchy with links
 */
function Breadcrumb({ className, homeLabel = 'Home', maxItems = 5, separator = 'chevron' }) {
  const location = useLocation();

  // Generate breadcrumb items from path
  const generateBreadcrumbs = () => {
    const pathnames = location.pathname.split('/').filter((x) => x);
    const breadcrumbs = [];

    // Add home
    breadcrumbs.push({
      label: homeLabel,
      href: ROUTES.HOME,
      isCurrent: pathnames.length === 0,
    });

    // Build path and add each segment
    let currentPath = '';
    pathnames.forEach((segment, index) => {
      currentPath += `/${segment}`;

      // Handle special routes
      let label = segment;
      let href = currentPath;

      // Decode and format label
      label = decodeURIComponent(segment)
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

      // Handle admin routes
      if (segment === 'admin') {
        label = 'Admin';
      } else if (segment === 'dashboard') {
        label = 'Dashboard';
      } else if (segment === 'posts') {
        label = 'Posts';
      } else if (segment === 'categories') {
        label = 'Categories';
      } else if (segment === 'tags') {
        label = 'Tags';
      } else if (segment === 'profile') {
        label = 'Profile';
      } else if (segment === 'settings') {
        label = 'Settings';
      } else if (segment === 'login' || segment === 'register') {
        label = segment.charAt(0).toUpperCase() + segment.slice(1);
      } else if (segment === 'create') {
        label = 'Create';
      } else if (segment === 'edit') {
        label = 'Edit';
      }

      // Check if this is the current page
      const isCurrent = index === pathnames.length - 1;

      breadcrumbs.push({
        label,
        href: isCurrent ? null : href,
        isCurrent,
      });
    });

    // Limit items if too many (keep first, current, and some in between)
    if (breadcrumbs.length > maxItems) {
      const trimmed = [breadcrumbs[0]];
      const middleCount = maxItems - 2;
      const skipStart = Math.floor((breadcrumbs.length - 1 - middleCount) / 2);

      if (skipStart > 1) {
        trimmed.push({ label: '...', href: null, isCurrent: false, isEllipsis: true });
      }

      trimmed.push(...breadcrumbs.slice(skipStart, skipStart + middleCount));

      if (skipStart + middleCount < breadcrumbs.length - 1) {
        trimmed.push({ label: '...', href: null, isCurrent: false, isEllipsis: true });
      }

      trimmed.push(breadcrumbs[breadcrumbs.length - 1]);
      return trimmed;
    }

    return breadcrumbs;
  };

  const breadcrumbs = generateBreadcrumbs();

  // Don't show breadcrumbs on home page
  if (location.pathname === '/') {
    return null;
  }

  return (
    <nav
      className={cn('flex items-center gap-1 text-sm', className)}
      aria-label="Breadcrumb"
    >
      {breadcrumbs.map((crumb, index) => (
        <div key={crumb.href || crumb.label} className="flex items-center">
          {/* Separator */}
          {index > 0 && !crumb.isEllipsis && (
            <span className="mx-1 text-secondary-400" aria-hidden="true">
              {separator === 'chevron' ? (
                <ChevronRight className="w-4 h-4" />
              ) : (
                '/'
              )}
            </span>
          )}

          {/* Breadcrumb Item */}
          {crumb.isEllipsis ? (
            <span className="px-2 text-secondary-400">{crumb.label}</span>
          ) : crumb.isCurrent ? (
            <span
              className={cn(
                'px-2 py-1 rounded-md font-medium',
                'text-secondary-900 dark:text-secondary-100',
                'bg-secondary-100 dark:bg-secondary-800'
              )}
              aria-current="page"
            >
              {crumb.label}
            </span>
          ) : (
            <Link
              to={crumb.href}
              className={cn(
                'px-2 py-1 rounded-md font-medium transition-colors',
                'text-secondary-600 dark:text-secondary-400',
                'hover:text-secondary-900 dark:hover:text-secondary-100',
                'hover:bg-secondary-100 dark:hover:bg-secondary-800'
              )}
            >
              {crumb.label}
            </Link>
          )}
        </div>
      ))}
    </nav>
  );
}

/**
 * CompactBreadcrumb for mobile - shows only home and current
 */
export function CompactBreadcrumb({ className, homeLabel = 'Home' }) {
  const location = useLocation();

  if (location.pathname === '/') {
    return null;
  }

  const pathnames = location.pathname.split('/').filter((x) => x);
  const currentSegment = pathnames[pathnames.length - 1];
  const currentLabel = decodeURIComponent(currentSegment)
    .split('-')
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');

  return (
    <nav className={cn('flex items-center gap-1 text-sm', className)} aria-label="Breadcrumb">
      <Link
        to={ROUTES.HOME}
        className="flex items-center gap-1 px-2 py-1 rounded-md text-secondary-600 dark:text-secondary-400 hover:text-secondary-900 dark:hover:text-secondary-100 hover:bg-secondary-100 dark:hover:bg-secondary-800 transition-colors"
      >
        <Home className="w-4 h-4" />
        <span className="sr-only">{homeLabel}</span>
      </Link>
      <ChevronRight className="w-4 h-4 text-secondary-400" />
      <span
        className="px-2 py-1 rounded-md font-medium text-secondary-900 dark:text-secondary-100 bg-secondary-100 dark:bg-secondary-800 truncate max-w-[150px]"
        aria-current="page"
      >
        {currentLabel}
      </span>
    </nav>
  );
}

export default Breadcrumb;
