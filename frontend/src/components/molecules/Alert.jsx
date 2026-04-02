import { useState, useEffect } from 'react';
import { cn } from '@/utils';
import { AlertCircle, CheckCircle2, XCircle, Info } from 'lucide-react';

/**
 * Alert component for displaying messages
 * @param {Object} props - Component props
 * @param {'info' | 'success' | 'warning' | 'danger'} props.variant - Alert variant
 * @param {string} props.title - Alert title
 * @param {React.ReactNode} props.children - Alert content
 * @param {boolean} props.dismissible - Show dismiss button
 * @param {Function} props.onDismiss - Dismiss handler
 */
function Alert({
  variant = 'info',
  title,
  children,
  dismissible = false,
  onDismiss,
  className,
  ...props
}) {
  const [isVisible, setIsVisible] = useState(true);

  useEffect(() => {
    if (!isVisible && onDismiss) {
      onDismiss();
    }
  }, [isVisible, onDismiss]);

  if (!isVisible) return null;

  const variants = {
    info: {
      bg: 'bg-info-50 dark:bg-info-900/20',
      border: 'border-info-200 dark:border-info-800',
      icon: Info,
      iconColor: 'text-info-600 dark:text-info-400',
      title: 'text-info-800 dark:text-info-200',
      text: 'text-info-700 dark:text-info-300',
    },
    success: {
      bg: 'bg-success-50 dark:bg-success-900/20',
      border: 'border-success-200 dark:border-success-800',
      icon: CheckCircle2,
      iconColor: 'text-success-600 dark:text-success-400',
      title: 'text-success-800 dark:text-success-200',
      text: 'text-success-700 dark:text-success-300',
    },
    warning: {
      bg: 'bg-warning-50 dark:bg-warning-900/20',
      border: 'border-warning-200 dark:border-warning-800',
      icon: AlertCircle,
      iconColor: 'text-warning-600 dark:text-warning-400',
      title: 'text-warning-800 dark:text-warning-200',
      text: 'text-warning-700 dark:text-warning-300',
    },
    danger: {
      bg: 'bg-danger-50 dark:bg-danger-900/20',
      border: 'border-danger-200 dark:border-danger-800',
      icon: XCircle,
      iconColor: 'text-danger-600 dark:text-danger-400',
      title: 'text-danger-800 dark:text-danger-200',
      text: 'text-danger-700 dark:text-danger-300',
    },
  };

  const style = variants[variant];
  const Icon = style.icon;

  return (
    <div
      className={cn(
        'relative p-4 rounded-lg border',
        style.bg,
        style.border,
        className
      )}
      role="alert"
      {...props}
    >
      <div className="flex gap-3">
        <Icon className={cn('w-5 h-5 flex-shrink-0', style.iconColor)} />
        <div className="flex-1">
          {title && (
            <p className={cn('font-medium mb-1', style.title)}>{title}</p>
          )}
          <div className={cn('text-sm', style.text)}>{children}</div>
        </div>
        {dismissible && (
          <button
            onClick={() => setIsVisible(false)}
            className={cn('flex-shrink-0 p-1 rounded hover:bg-black/5', style.text)}
            aria-label="Dismiss alert"
          >
            <XCircle className="w-4 h-4" />
          </button>
        )}
      </div>
    </div>
  );
}

export default Alert;
