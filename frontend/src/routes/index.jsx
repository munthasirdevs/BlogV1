import { lazy, Suspense } from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { PageLoader } from '@/components';
import { MainLayout, AuthLayout, DashboardLayout } from '@/layouts';
import { ProtectedRoute, PublicRoute, AdminRoute } from './RouteGuards';
import { ROUTES } from '@/constants';

// Lazy load pages for code splitting
const HomePage = lazy(() => import('@/pages/HomePage'));
const PostsPage = lazy(() => import('@/pages/PostsPage'));
const PostDetailPage = lazy(() => import('@/pages/PostDetailPage'));
const LoginPage = lazy(() => import('@/pages/LoginPage'));
const RegisterPage = lazy(() => import('@/pages/RegisterPage'));
const ForgotPasswordPage = lazy(() => import('@/pages/ForgotPasswordPage'));
const ResetPasswordPage = lazy(() => import('@/pages/ResetPasswordPage'));
const EmailVerificationPage = lazy(() => import('@/pages/EmailVerificationPage'));
const CategoriesPage = lazy(() => import('@/pages/CategoriesPage'));
const CategoryArchivePage = lazy(() => import('@/pages/CategoryArchivePage'));
const TagsPage = lazy(() => import('@/pages/TagsPage'));
const TagArchivePage = lazy(() => import('@/pages/TagArchivePage'));
const ProfilePage = lazy(() => import('@/pages/ProfilePage'));
const AuthorPage = lazy(() => import('@/pages/AuthorPage'));
const SettingsPage = lazy(() => import('@/pages/SettingsPage'));
const SearchPage = lazy(() => import('@/pages/SearchPage'));
const AboutPage = lazy(() => import('@/pages/AboutPage'));
const ContactPage = lazy(() => import('@/pages/ContactPage'));

// Admin pages
const AdminDashboard = lazy(() => import('@/pages/admin/DashboardPage'));
const AdminPosts = lazy(() => import('@/pages/admin/PostsPage'));
const AdminUsers = lazy(() => import('@/pages/admin/UsersPage'));
const AdminCategories = lazy(() => import('@/pages/admin/CategoriesPage'));
const AdminSettings = lazy(() => import('@/pages/admin/SettingsPage'));

// Error pages
const NotFoundPage = lazy(() => import('@/pages/NotFoundPage'));

/**
 * Page wrapper with Suspense for lazy loading
 */
function PageWrapper({ children }) {
  return (
    <Suspense fallback={<PageLoader message="Loading page..." />}>
      {children}
    </Suspense>
  );
}

/**
 * Main application routes
 */
function AppRoutes() {
  return (
    <Routes>
      {/* Public Routes with Main Layout */}
      <Route
        path={ROUTES.HOME}
        element={
          <PageWrapper>
            <MainLayout>
              <HomePage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.POSTS}
        element={
          <PageWrapper>
            <MainLayout>
              <PostsPage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/posts/:slug"
        element={
          <PageWrapper>
            <MainLayout>
              <PostDetailPage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.CATEGORIES}
        element={
          <PageWrapper>
            <MainLayout>
              <CategoriesPage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/categories/:slug"
        element={
          <PageWrapper>
            <MainLayout>
              <CategoryArchivePage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.TAGS}
        element={
          <PageWrapper>
            <MainLayout>
              <TagsPage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/tags/:slug"
        element={
          <PageWrapper>
            <MainLayout>
              <TagArchivePage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/search"
        element={
          <PageWrapper>
            <MainLayout>
              <SearchPage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/about"
        element={
          <PageWrapper>
            <MainLayout>
              <AboutPage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/contact"
        element={
          <PageWrapper>
            <MainLayout>
              <ContactPage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/author/:username"
        element={
          <PageWrapper>
            <MainLayout>
              <AuthorPage />
            </MainLayout>
          </PageWrapper>
        }
      />

      {/* Auth Routes with Auth Layout */}
      <Route
        path={ROUTES.LOGIN}
        element={
          <PageWrapper>
            <PublicRoute>
              <AuthLayout>
                <LoginPage />
              </AuthLayout>
            </PublicRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.REGISTER}
        element={
          <PageWrapper>
            <PublicRoute>
              <AuthLayout>
                <RegisterPage />
              </AuthLayout>
            </PublicRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.FORGOT_PASSWORD}
        element={
          <PageWrapper>
            <PublicRoute>
              <AuthLayout>
                <ForgotPasswordPage />
              </AuthLayout>
            </PublicRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.RESET_PASSWORD}
        element={
          <PageWrapper>
            <PublicRoute>
              <AuthLayout>
                <ResetPasswordPage />
              </AuthLayout>
            </PublicRoute>
          </PageWrapper>
        }
      />
      
      {/* Email Verification Routes */}
      <Route
        path={ROUTES.VERIFY_EMAIL_PENDING}
        element={
          <PageWrapper>
            <AuthLayout>
              <EmailVerificationPage />
            </AuthLayout>
          </PageWrapper>
        }
      />
      <Route
        path="/verify-email"
        element={
          <PageWrapper>
            <AuthLayout>
              <EmailVerificationPage />
            </AuthLayout>
          </PageWrapper>
        }
      />

      {/* Protected Routes */}
      <Route
        path={ROUTES.PROFILE(':username')}
        element={
          <PageWrapper>
            <MainLayout>
              <ProfilePage />
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.SETTINGS}
        element={
          <PageWrapper>
            <ProtectedRoute>
              <MainLayout>
                <SettingsPage />
              </MainLayout>
            </ProtectedRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.BOOKMARKS}
        element={
          <PageWrapper>
            <ProtectedRoute>
              <MainLayout>
                <div className="container mx-auto px-4 py-8">
                  <h1 className="text-2xl font-bold">Bookmarks</h1>
                  <p className="text-secondary-600 dark:text-secondary-400 mt-2">
                    Your bookmarked posts will appear here.
                  </p>
                </div>
              </MainLayout>
            </ProtectedRoute>
          </PageWrapper>
        }
      />

      {/* Admin Routes */}
      <Route
        path={ROUTES.ADMIN}
        element={
          <PageWrapper>
            <AdminRoute>
              <DashboardLayout>
                <Navigate to={ROUTES.ADMIN_DASHBOARD} replace />
              </DashboardLayout>
            </AdminRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.ADMIN_DASHBOARD}
        element={
          <PageWrapper>
            <AdminRoute>
              <DashboardLayout>
                <AdminDashboard />
              </DashboardLayout>
            </AdminRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.ADMIN_POSTS}
        element={
          <PageWrapper>
            <AdminRoute>
              <DashboardLayout>
                <AdminPosts />
              </DashboardLayout>
            </AdminRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.ADMIN_USERS}
        element={
          <PageWrapper>
            <AdminRoute>
              <DashboardLayout>
                <AdminUsers />
              </DashboardLayout>
            </AdminRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.ADMIN_CATEGORIES}
        element={
          <PageWrapper>
            <AdminRoute>
              <DashboardLayout>
                <AdminCategories />
              </DashboardLayout>
            </AdminRoute>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.ADMIN_SETTINGS}
        element={
          <PageWrapper>
            <AdminRoute>
              <DashboardLayout>
                <AdminSettings />
              </DashboardLayout>
            </AdminRoute>
          </PageWrapper>
        }
      />

      {/* Placeholder pages for Terms and Privacy */}
      <Route
        path={ROUTES.TERMS}
        element={
          <PageWrapper>
            <MainLayout>
              <div className="container mx-auto px-4 py-8 max-w-3xl">
                <h1 className="text-3xl font-bold mb-6">Terms of Service</h1>
                <div className="prose dark:prose-invert max-w-none">
                  <p className="text-secondary-600 dark:text-secondary-400">
                    Our Terms of Service are coming soon. Please check back later.
                  </p>
                </div>
              </div>
            </MainLayout>
          </PageWrapper>
        }
      />
      <Route
        path={ROUTES.PRIVACY}
        element={
          <PageWrapper>
            <MainLayout>
              <div className="container mx-auto px-4 py-8 max-w-3xl">
                <h1 className="text-3xl font-bold mb-6">Privacy Policy</h1>
                <div className="prose dark:prose-invert max-w-none">
                  <p className="text-secondary-600 dark:text-secondary-400">
                    Our Privacy Policy is coming soon. Please check back later.
                  </p>
                </div>
              </div>
            </MainLayout>
          </PageWrapper>
        }
      />

      {/* 404 Page */}
      <Route
        path="*"
        element={
          <PageWrapper>
            <MainLayout>
              <NotFoundPage />
            </MainLayout>
          </PageWrapper>
        }
      />
    </Routes>
  );
}

export default AppRoutes;
