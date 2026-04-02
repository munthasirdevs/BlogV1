import { cn } from '@/utils';
import { ArrowUp } from 'lucide-react';
import { useScroll } from '@/hooks';

/**
 * ScrollToTop button component
 * Shows after scrolling down and smoothly scrolls to top on click
 */
function ScrollToTop({
  className,
  scrollThreshold = 300,
  position = 'bottom-right',
  size = 'md',
  showTooltip = true,
}) {
  const { scrollY, isAtTop, scrollToTop } = useScroll({ scrollThreshold });

  const isVisible = scrollY > scrollThreshold && !isAtTop;

  const sizes = {
    sm: 'w-8 h-8 p-1.5',
    md: 'w-10 h-10 p-2',
    lg: 'w-12 h-12 p-2.5',
  };

  const positions = {
    'bottom-right': 'bottom-6 right-6',
    'bottom-left': 'bottom-6 left-6',
    'bottom-center': 'bottom-6 left-1/2 -translate-x-1/2',
  };

  return (
    <>
      <button
        onClick={scrollToTop}
        className={cn(
          'fixed z-40 rounded-full shadow-lg',
          'bg-primary-600 hover:bg-primary-700',
          'text-white',
          'transition-all duration-300 ease-in-out',
          'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
          'dark:focus:ring-offset-secondary-900',
          sizes[size],
          positions[position],
          isVisible
            ? 'opacity-100 translate-y-0 pointer-events-auto'
            : 'opacity-0 translate-y-4 pointer-events-none',
          className
        )}
        aria-label="Scroll to top"
        title={showTooltip ? 'Scroll to top' : undefined}
      >
        <ArrowUp className="w-full h-full" />
      </button>

      {/* Optional: Progress indicator ring */}
      <ScrollProgress className={positions[position]} />
    </>
  );
}

/**
 * ScrollProgress component - shows scroll progress as a ring
 */
function ScrollProgress({ className }) {
  const { scrollY } = useScroll();

  // Calculate scroll progress
  const getScrollProgress = () => {
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    return docHeight > 0 ? (scrollY / docHeight) * 100 : 0;
  };

  const progress = getScrollProgress();
  const circumference = 2 * Math.PI * 16; // radius = 16
  const strokeDashoffset = circumference - (progress / 100) * circumference;

  return (
    <div
      className={cn(
        'fixed z-30 w-12 h-12 pointer-events-none',
        'bottom-6 right-6',
        className
      )}
    >
      <svg className="w-full h-full -rotate-90" viewBox="0 0 36 36">
        {/* Background circle */}
        <circle
          cx="18"
          cy="18"
          r="16"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          className="text-secondary-200 dark:text-secondary-800"
        />
        {/* Progress circle */}
        <circle
          cx="18"
          cy="18"
          r="16"
          fill="none"
          stroke="currentColor"
          strokeWidth="2"
          strokeDasharray={circumference}
          strokeDashoffset={strokeDashoffset}
          strokeLinecap="round"
          className="text-primary-600 transition-all duration-150 ease-out"
        />
      </svg>
    </div>
  );
}

/**
 * ScrollToTopWithProgress - combines button with progress ring
 */
export function ScrollToTopWithProgress(props) {
  return <ScrollToTop {...props} />;
}

export default ScrollToTop;
