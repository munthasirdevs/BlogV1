import { cn } from '@/utils';

/**
 * Skeleton component for loading states
 * @param {Object} props - Component props
 * @param {'text' | 'circle' | 'rect' | 'rounded'} props.variant - Skeleton variant
 * @param {string} props.className - Additional CSS classes
 */
function Skeleton({ variant = 'text', className, ...props }) {
  const baseStyles = cn(
    'animate-pulse bg-secondary-200 dark:bg-secondary-700',
    {
      'h-4 rounded': variant === 'text',
      'rounded-full': variant === 'circle',
      'rounded': variant === 'rect',
      'rounded-lg': variant === 'rounded',
    },
    className
  );

  return <div className={baseStyles} {...props} />;
}

export default Skeleton;
