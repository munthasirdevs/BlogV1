import { cn } from '@/utils';

/**
 * Badge component for displaying status, labels, or counts
 * @param {Object} props - Component props
 * @param {'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info'} props.variant - Badge variant
 * @param {'sm' | 'md' | 'lg'} props.size - Badge size
 * @param {string} props.className - Additional CSS classes
 * @param {React.ReactNode} props.children - Badge content
 */
function Badge({
  variant = 'primary',
  size = 'md',
  className,
  children,
  ...props
}) {
  const baseStyles = cn(
    'inline-flex items-center font-medium rounded-full',
    {
      // Variants
      'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200':
        variant === 'primary',
      'bg-secondary-100 text-secondary-800 dark:bg-secondary-800 dark:text-secondary-200':
        variant === 'secondary',
      'bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200':
        variant === 'success',
      'bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200':
        variant === 'warning',
      'bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200':
        variant === 'danger',
      'bg-info-100 text-info-800 dark:bg-info-900 dark:text-info-200':
        variant === 'info',
      // Sizes
      'px-2 py-0.5 text-xs': size === 'sm',
      'px-2.5 py-0.5 text-xs': size === 'md',
      'px-3 py-1 text-sm': size === 'lg',
    },
    className
  );

  return (
    <span className={baseStyles} {...props}>
      {children}
    </span>
  );
}

export default Badge;
