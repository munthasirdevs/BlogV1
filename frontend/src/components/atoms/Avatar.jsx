import { cn } from '@/utils';

/**
 * Avatar component for displaying user images or initials
 * @param {Object} props - Component props
 * @param {string} props.src - Image source URL
 * @param {string} props.alt - Alt text for the image
 * @param {string} props.name - User name for generating initials
 * @param {'xs' | 'sm' | 'md' | 'lg' | 'xl'} props.size - Avatar size
 * @param {string} props.className - Additional CSS classes
 */
function Avatar({ src, alt, name, size = 'md', className, ...props }) {
  const sizeStyles = {
    xs: 'w-6 h-6 text-xs',
    sm: 'w-8 h-8 text-sm',
    md: 'w-10 h-10 text-base',
    lg: 'w-12 h-12 text-lg',
    xl: 'w-16 h-16 text-xl',
  };

  const getInitials = (name) => {
    if (!name) return '?';
    const names = name.split(' ');
    const initials =
      names.length > 1
        ? `${names[0][0]}${names[names.length - 1][0]}`
        : names[0][0];
    return initials.toUpperCase();
  };

  const avatarColors = [
    'bg-primary-500',
    'bg-secondary-500',
    'bg-accent-500',
    'bg-warning-500',
    'bg-danger-500',
    'bg-info-500',
  ];

  const getColorIndex = (name) => {
    if (!name) return 0;
    return name.charCodeAt(0) % avatarColors.length;
  };

  return (
    <div
      className={cn(
        'relative inline-flex items-center justify-center rounded-full overflow-hidden',
        'flex-shrink-0',
        sizeStyles[size],
        className
      )}
      {...props}
    >
      {src ? (
        <img
          src={src}
          alt={alt || name || 'Avatar'}
          className="w-full h-full object-cover"
        />
      ) : (
        <div
          className={cn(
            'w-full h-full flex items-center justify-center font-semibold text-white',
            avatarColors[getColorIndex(name)]
          )}
        >
          {getInitials(name)}
        </div>
      )}
    </div>
  );
}

export default Avatar;
