import { cn } from '@/utils';

/**
 * Container component for consistent page widths
 */
export function Container({ size = 'xl', className, children, ...props }) {
  const sizes = {
    sm: 'max-w-2xl',
    md: 'max-w-4xl',
    lg: 'max-w-5xl',
    xl: 'max-w-7xl',
    full: 'max-w-full',
  };

  return (
    <div className={cn('mx-auto px-4 sm:px-6 lg:px-8', sizes[size], className)} {...props}>
      {children}
    </div>
  );
}

/**
 * Section component for page sections
 */
export function Section({ spacing = 'md', className, children, ...props }) {
  const spacingStyles = {
    sm: 'py-8',
    md: 'py-12',
    lg: 'py-16',
    xl: 'py-20',
  };

  return (
    <section className={cn(spacingStyles[spacing], className)} {...props}>
      {children}
    </section>
  );
}

/**
 * Grid component for responsive layouts
 */
export function Grid({ cols = 3, gap = 'md', className, children, ...props }) {
  const colsStyles = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
  };

  const gapStyles = {
    sm: 'gap-4',
    md: 'gap-6',
    lg: 'gap-8',
    xl: 'gap-10',
  };

  return (
    <div className={cn('grid', colsStyles[cols], gapStyles[gap], className)} {...props}>
      {children}
    </div>
  );
}

/**
 * Flex component for flexbox layouts
 */
export function Flex({ direction = 'row', justify, align, gap = 'md', wrap = true, className, children, ...props }) {
  const directionStyles = {
    row: 'flex-row',
    col: 'flex-col',
  };

  const justifyStyles = {
    start: 'justify-start',
    center: 'justify-center',
    end: 'justify-end',
    between: 'justify-between',
    around: 'justify-around',
    evenly: 'justify-evenly',
  };

  const alignStyles = {
    start: 'items-start',
    center: 'items-center',
    end: 'items-end',
    stretch: 'items-stretch',
    baseline: 'items-baseline',
  };

  const gapStyles = {
    sm: 'gap-2',
    md: 'gap-4',
    lg: 'gap-6',
    xl: 'gap-8',
  };

  return (
    <div
      className={cn(
        'flex',
        directionStyles[direction],
        justify && justifyStyles[justify],
        align && alignStyles[align],
        gapStyles[gap],
        wrap && 'flex-wrap',
        className
      )}
      {...props}
    >
      {children}
    </div>
  );
}

/**
 * Stack component for vertical spacing
 */
export function Stack({ gap = 'md', className, children, ...props }) {
  const gapStyles = {
    sm: 'space-y-2',
    md: 'space-y-4',
    lg: 'space-y-6',
    xl: 'space-y-8',
  };

  return (
    <div className={cn(gapStyles[gap], className)} {...props}>
      {children}
    </div>
  );
}

/**
 * Divider component
 */
export function Divider({ orientation = 'horizontal', className, ...props }) {
  return (
    <hr
      className={cn(
        'border-secondary-200 dark:border-secondary-700',
        orientation === 'horizontal' ? 'my-4' : 'mx-4 h-full',
        className
      )}
      {...props}
    />
  );
}

/**
 * Spacer component
 */
export function Spacer({ size = 'md', className, ...props }) {
  const sizes = {
    xs: 'h-2',
    sm: 'h-4',
    md: 'h-6',
    lg: 'h-8',
    xl: 'h-12',
    '2xl': 'h-16',
  };

  return <div className={cn(sizes[size], className)} {...props} />;
}
