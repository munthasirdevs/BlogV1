import { cn } from '@/utils';

/**
 * Toast notification component
 * @param {Object} props - Component props
 * @param {'info' | 'success' | 'warning' | 'danger'} props.type - Toast type
 * @param {string} props.title - Toast title
 * @param {string} props.message - Toast message
 * @param {boolean} props.show - Show/hide toast
 */
function Toast({ type = 'info', title, message, show = false, onClose }) {
  if (!show) return null;

  const types = {
    info: {
      bg: 'bg-info-600',
      icon: 'ℹ️',
    },
    success: {
      bg: 'bg-success-600',
      icon: '✓',
    },
    warning: {
      bg: 'bg-warning-600',
      icon: '⚠',
    },
    danger: {
      bg: 'bg-danger-600',
      icon: '✕',
    },
  };

  const style = types[type];

  return (
    <div
      className={cn(
        'fixed bottom-4 right-4 z-50',
        'flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg',
        'text-white',
        style.bg,
        'animate-slide-in'
      )}
      role="alert"
    >
      <span className="text-lg">{style.icon}</span>
      <div className="flex-1">
        {title && <p className="font-medium text-sm">{title}</p>}
        {message && <p className="text-xs opacity-90">{message}</p>}
      </div>
      <button
        onClick={onClose}
        className="p-1 hover:bg-white/20 rounded transition-colors"
        aria-label="Close notification"
      >
        ✕
      </button>
    </div>
  );
}

export default Toast;
