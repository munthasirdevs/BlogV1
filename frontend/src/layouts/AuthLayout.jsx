import { Link } from 'react-router-dom';
import { PenSquare } from 'lucide-react';
import { ThemeToggle } from '@/components/organisms';
import { ROUTES } from '@/constants';
import { cn } from '@/utils';

/**
 * Auth layout component for login/register pages
 * @param {Object} props - Component props
 * @param {React.ReactNode} props.children - Page content
 */
function AuthLayout({ children, className, ...props }) {
  return (
    <div className="min-h-screen flex flex-col">
      {/* Header */}
      <header className="absolute top-0 left-0 right-0 z-10 p-4">
        <div className="container mx-auto flex items-center justify-between">
          <Link
            to={ROUTES.HOME}
            className="flex items-center gap-2 text-secondary-900 dark:text-secondary-100"
          >
            <div className="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center">
              <PenSquare className="w-5 h-5 text-white" />
            </div>
            <span className="text-xl font-bold">Blog</span>
          </Link>
          <ThemeToggle />
        </div>
      </header>

      {/* Main Content */}
      <main
        className={cn(
          'flex-1 flex items-center justify-center p-4',
          'bg-gradient-to-br from-secondary-50 to-secondary-100',
          'dark:from-secondary-900 dark:to-secondary-800',
          className
        )}
        {...props}
      >
        <div className="w-full max-w-md">{children}</div>
      </main>
    </div>
  );
}

export default AuthLayout;
