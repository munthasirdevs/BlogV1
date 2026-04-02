import { Spinner } from '@/components/atoms';
import { cn } from '@/utils';

/**
 * PageLoader component for full-page loading states
 */
function PageLoader({ message = 'Loading...', className, ...props }) {
  return (
    <div
      className={cn(
        'min-h-[400px] flex flex-col items-center justify-center gap-4',
        className
      )}
      {...props}
    >
      <Spinner size="lg" />
      {message && (
        <p className="text-sm text-secondary-600 dark:text-secondary-400 animate-pulse">
          {message}
        </p>
      )}
    </div>
  );
}

export default PageLoader;
