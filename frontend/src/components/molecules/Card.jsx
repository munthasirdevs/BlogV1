import { cn } from '@/utils';
import { Skeleton } from '@/components/atoms';

/**
 * Card component for displaying content in a contained area
 * @param {Object} props - Component props
 * @param {React.ReactNode} props.children - Card content
 * @param {boolean} props.hoverable - Enable hover effect
 * @param {string} props.className - Additional CSS classes
 */
function Card({ children, hoverable = true, className, ...props }) {
  return (
    <div
      className={cn(
        'bg-white dark:bg-secondary-800 rounded-xl overflow-hidden',
        'border border-secondary-200 dark:border-secondary-700',
        hoverable && 'transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5',
        className
      )}
      {...props}
    >
      {children}
    </div>
  );
}

/**
 * Card Header component
 */
function CardHeader({ children, className, ...props }) {
  return (
    <div className={cn('px-6 py-4 border-b border-secondary-200 dark:border-secondary-700', className)} {...props}>
      {children}
    </div>
  );
}

/**
 * Card Title component
 */
function CardTitle({ children, className, ...props }) {
  return (
    <h3 className={cn('text-lg font-semibold text-secondary-900 dark:text-secondary-100', className)} {...props}>
      {children}
    </h3>
  );
}

/**
 * Card Description component
 */
function CardDescription({ children, className, ...props }) {
  return (
    <p className={cn('text-sm text-secondary-500 dark:text-secondary-400 mt-1', className)} {...props}>
      {children}
    </p>
  );
}

/**
 * Card Content component
 */
function CardContent({ children, className, ...props }) {
  return (
    <div className={cn('px-6 py-4', className)} {...props}>
      {children}
    </div>
  );
}

/**
 * Card Footer component
 */
function CardFooter({ children, className, ...props }) {
  return (
    <div className={cn('px-6 py-4 border-t border-secondary-200 dark:border-secondary-700 bg-secondary-50 dark:bg-secondary-900', className)} {...props}>
      {children}
    </div>
  );
}

/**
 * Card Skeleton for loading states
 */
function CardSkeleton({ showImage = true, showFooter = true }) {
  return (
    <Card hoverable={false}>
      {showImage && <Skeleton className="w-full h-48" />}
      <CardContent>
        <Skeleton className="w-3/4 h-6 mb-2" />
        <Skeleton className="w-full h-4 mb-2" />
        <Skeleton className="w-2/3 h-4" />
      </CardContent>
      {showFooter && (
        <CardFooter>
          <div className="flex justify-between">
            <Skeleton className="w-20 h-8" />
            <Skeleton className="w-20 h-8" />
          </div>
        </CardFooter>
      )}
    </Card>
  );
}

Card.Header = CardHeader;
Card.Title = CardTitle;
Card.Description = CardDescription;
Card.Content = CardContent;
Card.Footer = CardFooter;
Card.Skeleton = CardSkeleton;

export default Card;
