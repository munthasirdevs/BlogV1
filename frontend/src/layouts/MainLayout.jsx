import { Header, Footer } from '@/components/organisms';
import { cn } from '@/utils';

/**
 * Main layout component with header and footer
 * @param {Object} props - Component props
 * @param {React.ReactNode} props.children - Page content
 */
function MainLayout({ children, className, ...props }) {
  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <main className={cn('flex-1', className)} {...props}>
        {children}
      </main>
      <Footer />
    </div>
  );
}

export default MainLayout;
