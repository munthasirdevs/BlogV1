import { forwardRef } from 'react';
import { cn } from '@/utils';
import { Loader2 } from 'lucide-react';

/**
 * Button component with multiple variants and sizes
 * @param {Object} props - Component props
 * @param {'primary' | 'secondary' | 'outline' | 'ghost' | 'danger'} props.variant - Button variant
 * @param {'sm' | 'md' | 'lg'} props.size - Button size
 * @param {boolean} props.isLoading - Loading state
 * @param {boolean} props.disabled - Disabled state
 * @param {React.ReactNode} props.leftIcon - Icon on the left
 * @param {React.ReactNode} props.rightIcon - Icon on the right
 * @param {string} props.className - Additional CSS classes
 * @param {React.ReactNode} props.children - Button content
 */
const Button = forwardRef(
  (
    {
      variant = 'primary',
      size = 'md',
      isLoading = false,
      disabled = false,
      leftIcon,
      rightIcon,
      className,
      children,
      ...props
    },
    ref
  ) => {
    const baseStyles = cn(
      'inline-flex items-center justify-center font-medium transition-all duration-200',
      'focus:outline-none focus:ring-2 focus:ring-offset-2',
      'disabled:opacity-50 disabled:cursor-not-allowed',
      {
        // Variants
        'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500':
          variant === 'primary',
        'bg-secondary-100 text-secondary-900 hover:bg-secondary-200 dark:bg-secondary-800 dark:text-secondary-100 dark:hover:bg-secondary-700 focus:ring-secondary-500':
          variant === 'secondary',
        'border-2 border-secondary-300 dark:border-secondary-600 bg-transparent hover:bg-secondary-50 dark:hover:bg-secondary-800 focus:ring-secondary-500':
          variant === 'outline',
        'bg-transparent hover:bg-secondary-100 dark:hover:bg-secondary-800':
          variant === 'ghost',
        'bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500':
          variant === 'danger',
        // Sizes
        'px-3 py-1.5 text-sm rounded-md': size === 'sm',
        'px-4 py-2 text-sm rounded-lg': size === 'md',
        'px-6 py-3 text-base rounded-lg': size === 'lg',
      },
      className
    );

    return (
      <button
        ref={ref}
        className={baseStyles}
        disabled={disabled || isLoading}
        {...props}
      >
        {isLoading && (
          <Loader2 className="w-4 h-4 mr-2 animate-spin" aria-hidden="true" />
        )}
        {!isLoading && leftIcon && (
          <span className={children ? 'mr-2' : ''}>{leftIcon}</span>
        )}
        {children}
        {!isLoading && rightIcon && (
          <span className={children ? 'ml-2' : ''}>{rightIcon}</span>
        )}
      </button>
    );
  }
);

Button.displayName = 'Button';

export default Button;
