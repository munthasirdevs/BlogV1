import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/contexts/AuthContext';
import { PageLoader } from '@/components';
import { ROUTES } from '@/constants';

/**
 * Protected route wrapper - redirects to login if not authenticated
 * Preserves the intended destination for redirect after login
 */
export function ProtectedRoute({ children, redirectTo = ROUTES.LOGIN }) {
  const { isAuthenticated, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <PageLoader message="Verifying authentication..." />
      </div>
    );
  }

  if (!isAuthenticated) {
    // Redirect to login and save the location we were trying to access
    return <Navigate to={redirectTo} state={{ from: location }} replace />;
  }

  return children;
}

/**
 * Public route wrapper - redirects to home if already authenticated
 * Used for login, register, forgot password pages
 */
export function PublicRoute({ children, redirectTo = ROUTES.HOME }) {
  const { isAuthenticated, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <PageLoader message="Loading..." />
      </div>
    );
  }

  if (isAuthenticated) {
    // If user is authenticated and tries to access auth pages, redirect to home
    // or to the page they were trying to access before
    const from = location.state?.from?.pathname || redirectTo;
    return <Navigate to={from} replace />;
  }

  return children;
}

/**
 * Admin route wrapper - redirects if not admin
 * Requires authentication AND admin role
 */
export function AdminRoute({ children, redirectTo = ROUTES.HOME }) {
  const { user, isAuthenticated, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <PageLoader message="Loading..." />
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to={ROUTES.LOGIN} state={{ from: location }} replace />;
  }

  if (user?.role !== 'admin' && user?.is_admin !== true) {
    // User is authenticated but not an admin
    return <Navigate to={redirectTo} replace />;
  }

  return children;
}

/**
 * Guest route wrapper - only for unauthenticated users
 * Similar to PublicRoute but with stricter naming convention
 */
export function GuestRoute({ children, redirectTo = ROUTES.HOME }) {
  return <PublicRoute children={children} redirectTo={redirectTo} />;
}
