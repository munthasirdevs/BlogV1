import { useState } from 'react';
import { Link } from 'react-router-dom';
import { cn } from '@/utils';
import { PenSquare, Github, Twitter, Linkedin, Facebook, Mail, Check, Loader2, AlertCircle } from 'lucide-react';
import { ROUTES } from '@/constants';
import { newsletterService } from '@/services';

/**
 * Enhanced Footer component with newsletter signup and social links
 */
function Footer({ className, ...props }) {
  const currentYear = new Date().getFullYear();
  const [email, setEmail] = useState('');
  const [status, setStatus] = useState('idle'); // idle, loading, success, error
  const [message, setMessage] = useState('');

  const footerLinks = {
    navigation: [
      { label: 'Home', href: ROUTES.HOME },
      { label: 'Posts', href: ROUTES.POSTS },
      { label: 'Categories', href: ROUTES.CATEGORIES },
      { label: 'Tags', href: ROUTES.TAGS },
    ],
    company: [
      { label: 'About Us', href: ROUTES.ABOUT },
      { label: 'Contact', href: ROUTES.CONTACT },
      { label: 'Careers', href: '/careers' },
      { label: 'Advertise', href: '/advertise' },
    ],
    legal: [
      { label: 'Privacy Policy', href: '/privacy' },
      { label: 'Terms of Service', href: '/terms' },
      { label: 'Cookie Policy', href: '/cookies' },
      { label: 'GDPR', href: '/gdpr' },
    ],
    resources: [
      { label: 'Help Center', href: '/help' },
      { label: 'Documentation', href: '/docs' },
      { label: 'API', href: '/api' },
      { label: 'Status', href: '/status' },
    ],
  };

  const socialLinks = [
    { icon: Github, href: 'https://github.com', label: 'GitHub', color: 'hover:text-gray-900 dark:hover:text-gray-100' },
    { icon: Twitter, href: 'https://twitter.com', label: 'Twitter', color: 'hover:text-blue-400' },
    { icon: Facebook, href: 'https://facebook.com', label: 'Facebook', color: 'hover:text-blue-600' },
    { icon: Linkedin, href: 'https://linkedin.com', label: 'LinkedIn', color: 'hover:text-blue-700' },
  ];

  // Handle newsletter subscription
  const handleSubscribe = async (e) => {
    e.preventDefault();

    if (!email || !email.includes('@')) {
      setStatus('error');
      setMessage('Please enter a valid email address');
      return;
    }

    setStatus('loading');

    try {
      await newsletterService.subscribe(email);
      setStatus('success');
      setMessage('Thank you for subscribing! Please check your email to confirm.');
      setEmail('');
    } catch (error) {
      setStatus('error');
      setMessage(error.response?.data?.message || 'Failed to subscribe. Please try again.');
    }

    // Reset status after 5 seconds
    setTimeout(() => {
      setStatus('idle');
      setMessage('');
    }, 5000);
  };

  return (
    <footer
      className={cn(
        'bg-secondary-50 dark:bg-secondary-900 border-t border-secondary-200 dark:border-secondary-800',
        className
      )}
      {...props}
    >
      <div className="container mx-auto px-4 py-12 lg:py-16">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-12">
          {/* Brand Column */}
          <div className="lg:col-span-2">
            <Link to={ROUTES.HOME} className="flex items-center gap-2.5 mb-4">
              <div className="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                <PenSquare className="w-5 h-5 text-white" />
              </div>
              <span className="text-xl font-bold text-secondary-900 dark:text-secondary-100">
                Blog
              </span>
            </Link>
            <p className="text-sm text-secondary-600 dark:text-secondary-400 mb-6 max-w-sm">
              A modern blog platform for sharing ideas and knowledge with the world. 
              Join our community of writers and readers.
            </p>

            {/* Social Links */}
            <div className="flex gap-3 mb-6">
              {socialLinks.map((social) => (
                <a
                  key={social.label}
                  href={social.href}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={cn(
                    'p-2.5 rounded-lg bg-white dark:bg-secondary-800',
                    'text-secondary-600 dark:text-secondary-400',
                    'hover:bg-secondary-100 dark:hover:bg-secondary-700',
                    'shadow-sm hover:shadow transition-all',
                    social.color
                  )}
                  aria-label={social.label}
                >
                  <social.icon className="w-5 h-5" />
                </a>
              ))}
            </div>

            {/* Contact Email */}
            <div className="flex items-center gap-2 text-sm text-secondary-600 dark:text-secondary-400">
              <Mail className="w-4 h-4" />
              <a href="mailto:contact@blog.com" className="hover:text-primary-600 dark:hover:text-primary-400">
                contact@blog.com
              </a>
            </div>
          </div>

          {/* Navigation Column */}
          <div>
            <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100 mb-4">
              Navigation
            </h3>
            <ul className="space-y-2.5">
              {footerLinks.navigation.map((link) => (
                <li key={link.href}>
                  <Link
                    to={link.href}
                    className="text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Company Column */}
          <div>
            <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100 mb-4">
              Company
            </h3>
            <ul className="space-y-2.5">
              {footerLinks.company.map((link) => (
                <li key={link.href}>
                  <Link
                    to={link.href}
                    className="text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          {/* Newsletter Column */}
          <div>
            <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100 mb-4">
              Newsletter
            </h3>
            <p className="text-sm text-secondary-600 dark:text-secondary-400 mb-4">
              Subscribe to get the latest posts and updates delivered to your inbox.
            </p>

            {/* Newsletter Form */}
            <form onSubmit={handleSubscribe} className="space-y-3">
              <div className="relative">
                <input
                  type="email"
                  value={email}
                  onChange={(e) => {
                    setEmail(e.target.value);
                    if (status === 'error') setStatus('idle');
                  }}
                  placeholder="Enter your email"
                  disabled={status === 'loading' || status === 'success'}
                  className={cn(
                    'w-full px-4 py-2.5 text-sm rounded-lg border',
                    'bg-white dark:bg-secondary-800',
                    'text-secondary-900 dark:text-secondary-100',
                    'placeholder-secondary-500',
                    'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent',
                    status === 'error'
                      ? 'border-danger-300 dark:border-danger-700'
                      : 'border-secondary-300 dark:border-secondary-600'
                  )}
                  aria-label="Email address for newsletter"
                  aria-invalid={status === 'error'}
                />
                {status === 'success' && (
                  <Check className="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-green-500" />
                )}
              </div>

              <button
                type="submit"
                disabled={status === 'loading' || status === 'success'}
                className={cn(
                  'w-full flex items-center justify-center gap-2 px-4 py-2.5',
                  'text-sm font-medium text-white',
                  'bg-primary-600 hover:bg-primary-700',
                  'disabled:bg-primary-400 disabled:cursor-not-allowed',
                  'rounded-lg transition-all',
                  'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2'
                )}
              >
                {status === 'loading' ? (
                  <>
                    <Loader2 className="w-4 h-4 animate-spin" />
                    Subscribing...
                  </>
                ) : status === 'success' ? (
                  <>
                    <Check className="w-4 h-4" />
                    Subscribed!
                  </>
                ) : (
                  'Subscribe'
                )}
              </button>

              {/* Status Message */}
              {message && (
                <div
                  className={cn(
                    'flex items-start gap-2 text-xs',
                    status === 'error'
                      ? 'text-danger-600 dark:text-danger-400'
                      : 'text-green-600 dark:text-green-400'
                  )}
                  role="alert"
                >
                  {status === 'error' && <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />}
                  {message}
                </div>
              )}
            </form>
          </div>
        </div>

        {/* Legal Links - Mobile */}
        <div className="mt-8 lg:hidden">
          <div className="grid grid-cols-2 gap-8">
            <div>
              <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100 mb-4">
                Legal
              </h3>
              <ul className="space-y-2.5">
                {footerLinks.legal.map((link) => (
                  <li key={link.href}>
                    <Link
                      to={link.href}
                      className="text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
            <div>
              <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100 mb-4">
                Resources
              </h3>
              <ul className="space-y-2.5">
                {footerLinks.resources.map((link) => (
                  <li key={link.href}>
                    <Link
                      to={link.href}
                      className="text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        {/* Legal Links - Desktop */}
        <div className="hidden lg:grid lg:grid-cols-2 gap-8 mt-8">
          <div>
            <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100 mb-4">
              Legal
            </h3>
            <ul className="flex flex-wrap gap-x-6 gap-y-2">
              {footerLinks.legal.map((link) => (
                <li key={link.href}>
                  <Link
                    to={link.href}
                    className="text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
          <div>
            <h3 className="text-sm font-semibold text-secondary-900 dark:text-secondary-100 mb-4">
              Resources
            </h3>
            <ul className="flex flex-wrap gap-x-6 gap-y-2">
              {footerLinks.resources.map((link) => (
                <li key={link.href}>
                  <Link
                    to={link.href}
                    className="text-sm text-secondary-600 dark:text-secondary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="mt-12 pt-8 border-t border-secondary-200 dark:border-secondary-800">
          <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
            <p className="text-sm text-secondary-600 dark:text-secondary-400">
              © {currentYear} Blog Platform. All rights reserved.
            </p>
            <div className="flex items-center gap-4">
              <p className="text-sm text-secondary-500 dark:text-secondary-500">
                Built with React & Laravel
              </p>
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                <span className="text-sm text-secondary-500 dark:text-secondary-500">
                  All systems operational
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}

export default Footer;
