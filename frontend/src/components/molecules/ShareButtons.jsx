import { useState } from 'react';
import { cn } from '@/utils';
import { Button } from '@/components/atoms';
import { Share2, Twitter, Facebook, Linkedin, MessageCircle, Link as LinkIcon, Check } from 'lucide-react';

/**
 * ShareButtons component - Social sharing buttons
 * @param {Object} props - Component props
 * @param {string} props.url - URL to share
 * @param {string} props.title - Title of the content
 * @param {string} props.description - Description of the content
 * @param {string} props.variant - Button variant (default: 'outline')
 * @param {boolean} props.showCount - Show share counts (default: false)
 */
function ShareButtons({ url, title, description, variant = 'outline', showCount = false, className }) {
  const [copied, setCopied] = useState(false);

  const shareUrl = encodeURIComponent(url);
  const shareTitle = encodeURIComponent(title);
  const shareDescription = encodeURIComponent(description || title);

  const shareLinks = {
    twitter: `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareTitle}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`,
    linkedin: `https://www.linkedin.com/shareArticle?mini=true&url=${shareUrl}&title=${shareTitle}&summary=${shareDescription}`,
    whatsapp: `https://wa.me/?text=${shareTitle}%20${shareUrl}`,
  };

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  const handleShare = (platform) => {
    window.open(shareLinks[platform], '_blank', 'width=600,height=400');
  };

  const shareOptions = [
    { key: 'twitter', icon: Twitter, label: 'Twitter', color: 'hover:text-blue-400' },
    { key: 'facebook', icon: Facebook, label: 'Facebook', color: 'hover:text-blue-600' },
    { key: 'linkedin', icon: Linkedin, label: 'LinkedIn', color: 'hover:text-blue-700' },
    { key: 'whatsapp', icon: MessageCircle, label: 'WhatsApp', color: 'hover:text-green-500' },
  ];

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <span className="text-sm font-medium text-secondary-600 dark:text-secondary-400 mr-2">
        Share:
      </span>
      
      {/* Social share buttons */}
      {shareOptions.map((option) => (
        <Button
          key={option.key}
          variant={variant}
          size="sm"
          className={cn('p-2', option.color)}
          onClick={() => handleShare(option.key)}
          aria-label={`Share on ${option.label}`}
          title={`Share on ${option.label}`}
        >
          <option.icon className="w-4 h-4" />
        </Button>
      ))}

      {/* Copy link button */}
      <Button
        variant={variant}
        size="sm"
        className={cn('p-2', copied && 'text-green-500')}
        onClick={handleCopyLink}
        aria-label="Copy link"
        title="Copy link"
      >
        {copied ? (
          <Check className="w-4 h-4" />
        ) : (
          <LinkIcon className="w-4 h-4" />
        )}
      </Button>

      {/* Native share button (mobile) */}
      {navigator.share && (
        <Button
          variant={variant}
          size="sm"
          className="p-2 md:hidden"
          onClick={async () => {
            try {
              await navigator.share({ title, text: description, url });
            } catch (err) {
              console.error('Share failed:', err);
            }
          }}
          aria-label="Share"
          title="Share"
        >
          <Share2 className="w-4 h-4" />
        </Button>
      )}
    </div>
  );
}

export default ShareButtons;
