import { useState } from 'react';
import { Sidebar, DashboardHeader } from '@/components/organisms';
import { cn } from '@/utils';
import { useIsLg } from '@/hooks';

/**
 * Dashboard layout component for admin pages with header and collapsible sidebar
 * @param {Object} props - Component props
 * @param {React.ReactNode} props.children - Page content
 */
function DashboardLayout({ children, className, ...props }) {
  const isDesktop = useIsLg();
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const handleMenuClick = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  const handleSidebarCollapse = (collapsed) => {
    setSidebarCollapsed(collapsed);
  };

  return (
    <div className="min-h-screen flex bg-secondary-50 dark:bg-secondary-900">
      {/* Mobile Overlay */}
      {!isDesktop && mobileMenuOpen && (
        <div
          className="fixed inset-0 bg-black/50 z-40 lg:hidden"
          onClick={() => setMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar */}
      <div
        className={cn(
          'fixed lg:sticky top-0 left-0 z-50 h-screen',
          'transition-transform duration-300 ease-in-out',
          !isDesktop && !mobileMenuOpen && '-translate-x-full',
          isDesktop && 'translate-x-0'
        )}
      >
        <Sidebar
          collapsible={isDesktop}
          defaultCollapsed={sidebarCollapsed}
          onCollapse={handleSidebarCollapse}
          onClose={() => setMobileMenuOpen(false)}
        />
      </div>

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col min-w-0">
        <DashboardHeader onMenuClick={handleMenuClick} />
        <main
          className={cn('flex-1 overflow-auto', className)}
          {...props}
        >
          <div className="container mx-auto px-4 sm:px-6 py-8">
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}

export default DashboardLayout;
