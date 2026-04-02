import { cn } from '@/utils';

/**
 * Typography components for consistent text styling
 */

/**
 * H1 heading component
 */
export function H1({ className, children, ...props }) {
  return (
    <h1
      className={cn(
        'text-4xl font-bold text-secondary-900 dark:text-secondary-100',
        'tracking-tight',
        className
      )}
      {...props}
    >
      {children}
    </h1>
  );
}

/**
 * H2 heading component
 */
export function H2({ className, children, ...props }) {
  return (
    <h2
      className={cn(
        'text-3xl font-bold text-secondary-900 dark:text-secondary-100',
        'tracking-tight',
        className
      )}
      {...props}
    >
      {children}
    </h2>
  );
}

/**
 * H3 heading component
 */
export function H3({ className, children, ...props }) {
  return (
    <h3
      className={cn(
        'text-2xl font-semibold text-secondary-900 dark:text-secondary-100',
        className
      )}
      {...props}
    >
      {children}
    </h3>
  );
}

/**
 * H4 heading component
 */
export function H4({ className, children, ...props }) {
  return (
    <h4
      className={cn(
        'text-xl font-semibold text-secondary-900 dark:text-secondary-100',
        className
      )}
      {...props}
    >
      {children}
    </h4>
  );
}

/**
 * H5 heading component
 */
export function H5({ className, children, ...props }) {
  return (
    <h5
      className={cn(
        'text-lg font-semibold text-secondary-900 dark:text-secondary-100',
        className
      )}
      {...props}
    >
      {children}
    </h5>
  );
}

/**
 * H6 heading component
 */
export function H6({ className, children, ...props }) {
  return (
    <h6
      className={cn(
        'text-base font-semibold text-secondary-900 dark:text-secondary-100',
        className
      )}
      {...props}
    >
      {children}
    </h6>
  );
}

/**
 * Paragraph component
 */
export function Text({
  size = 'base',
  weight = 'normal',
  color = 'default',
  className,
  children,
  ...props
}) {
  const sizeStyles = {
    xs: 'text-xs',
    sm: 'text-sm',
    base: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl',
  };

  const weightStyles = {
    normal: 'font-normal',
    medium: 'font-medium',
    semibold: 'font-semibold',
    bold: 'font-bold',
  };

  const colorStyles = {
    default: 'text-secondary-900 dark:text-secondary-100',
    muted: 'text-secondary-600 dark:text-secondary-400',
    primary: 'text-primary-600 dark:text-primary-400',
    danger: 'text-danger-600 dark:text-danger-400',
    success: 'text-success-600 dark:text-success-400',
  };

  return (
    <p
      className={cn(
        sizeStyles[size],
        weightStyles[weight],
        colorStyles[color],
        'leading-relaxed',
        className
      )}
      {...props}
    >
      {children}
    </p>
  );
}

/**
 * Link component
 */
export function Link({ className, children, ...props }) {
  return (
    <a
      className={cn(
        'text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300',
        'underline-offset-4 hover:underline',
        'transition-colors duration-200',
        className
      )}
      {...props}
    >
      {children}
    </a>
  );
}
