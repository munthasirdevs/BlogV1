import { useState } from 'react';
import { H1, H2, Text, Button, Input, Textarea } from '@/components/atoms';
import { Alert } from '@/components/molecules';
import { Container, Section } from '@/components';
import { Mail, MapPin, Phone, Clock, Send, CheckCircle, AlertCircle } from 'lucide-react';
import { cn } from '@/utils';
import api from '@/services/api';

/**
 * ContactPage - Contact form with validation
 */
function ContactPage() {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: '',
    message: '',
  });
  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState('idle'); // idle, submitting, success, error
  const [statusMessage, setStatusMessage] = useState('');

  const contactInfo = [
    {
      icon: Mail,
      title: 'Email',
      value: 'contact@blog.com',
      href: 'mailto:contact@blog.com',
      description: 'We typically respond within 24 hours',
    },
    {
      icon: MapPin,
      title: 'Address',
      value: '123 Blog Street, Tech City',
      href: null,
      description: 'Visit our headquarters',
    },
    {
      icon: Phone,
      title: 'Phone',
      value: '+1 (555) 123-4567',
      href: 'tel:+15551234567',
      description: 'Mon-Fri, 9am-6pm EST',
    },
    {
      icon: Clock,
      title: 'Response Time',
      value: '24-48 hours',
      href: null,
      description: 'Average response time',
    },
  ];

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Name is required';
    } else if (formData.name.trim().length < 2) {
      newErrors.name = 'Name must be at least 2 characters';
    }

    if (!formData.email.trim()) {
      newErrors.email = 'Email is required';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = 'Please enter a valid email address';
    }

    if (!formData.subject.trim()) {
      newErrors.subject = 'Subject is required';
    } else if (formData.subject.trim().length < 3) {
      newErrors.subject = 'Subject must be at least 3 characters';
    }

    if (!formData.message.trim()) {
      newErrors.message = 'Message is required';
    } else if (formData.message.trim().length < 10) {
      newErrors.message = 'Message must be at least 10 characters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateForm()) {
      return;
    }

    setStatus('submitting');

    try {
      // Send to backend API
      await api.post('/contact', formData);
      setStatus('success');
      setStatusMessage('Thank you for your message! We will get back to you soon.');
      setFormData({ name: '', email: '', subject: '', message: '' });
    } catch (error) {
      setStatus('error');
      setStatusMessage(error.response?.data?.message || 'Failed to send message. Please try again.');
    }

    // Reset status after 5 seconds
    setTimeout(() => {
      setStatus('idle');
      setStatusMessage('');
    }, 5000);
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: '' }));
    }
  };

  return (
    <Section spacing="xl">
      <Container>
        {/* Header */}
        <div className="text-center max-w-2xl mx-auto mb-12">
          <H1 className="mb-4">Get in Touch</H1>
          <Text size="lg" color="muted">
            Have a question, suggestion, or just want to say hello? 
            We'd love to hear from you. Fill out the form below and we'll get back to you as soon as possible.
          </Text>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
          {/* Contact Info */}
          <div className="lg:col-span-1 space-y-6">
            <H2 className="text-xl font-bold mb-6">Contact Information</H2>
            {contactInfo.map((info) => (
              <div key={info.title} className="flex gap-4">
                <div className="flex-shrink-0">
                  <div className="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <info.icon className="w-5 h-5 text-primary-600 dark:text-primary-400" />
                  </div>
                </div>
                <div>
                  <h3 className="font-semibold text-secondary-900 dark:text-secondary-100 mb-1">
                    {info.title}
                  </h3>
                  {info.href ? (
                    <a
                      href={info.href}
                      className="text-primary-600 dark:text-primary-400 hover:underline"
                    >
                      {info.value}
                    </a>
                  ) : (
                    <p className="text-secondary-700 dark:text-secondary-300">{info.value}</p>
                  )}
                  <p className="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                    {info.description}
                  </p>
                </div>
              </div>
            ))}

            {/* Map Placeholder */}
            <div className="mt-8 rounded-xl overflow-hidden bg-secondary-200 dark:bg-secondary-700 aspect-video">
              <div className="w-full h-full flex items-center justify-center text-secondary-500 dark:text-secondary-400">
                <MapPin className="w-12 h-12" />
              </div>
            </div>
          </div>

          {/* Contact Form */}
          <div className="lg:col-span-2">
            <div className="bg-white dark:bg-secondary-800 rounded-2xl border border-secondary-200 dark:border-secondary-700 p-6 md:p-8">
              <H2 className="text-xl font-bold mb-6">Send us a Message</H2>

              {/* Status Messages */}
              {status === 'success' && (
                <Alert variant="success" className="mb-6" title="Message Sent!">
                  {statusMessage}
                </Alert>
              )}
              {status === 'error' && (
                <Alert variant="danger" className="mb-6" title="Error">
                  {statusMessage}
                </Alert>
              )}

              <form onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
                  {/* Name */}
                  <div>
                    <label
                      htmlFor="name"
                      className="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2"
                    >
                      Your Name <span className="text-danger-500">*</span>
                    </label>
                    <Input
                      id="name"
                      name="name"
                      type="text"
                      value={formData.name}
                      onChange={handleChange}
                      placeholder="John Doe"
                      className={cn(errors.name && 'border-danger-500 focus:ring-danger-500')}
                      aria-invalid={!!errors.name}
                      aria-describedby={errors.name ? 'name-error' : undefined}
                    />
                    {errors.name && (
                      <p id="name-error" className="mt-1 text-sm text-danger-500 flex items-center gap-1">
                        <AlertCircle className="w-3 h-3" />
                        {errors.name}
                      </p>
                    )}
                  </div>

                  {/* Email */}
                  <div>
                    <label
                      htmlFor="email"
                      className="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2"
                    >
                      Email Address <span className="text-danger-500">*</span>
                    </label>
                    <Input
                      id="email"
                      name="email"
                      type="email"
                      value={formData.email}
                      onChange={handleChange}
                      placeholder="john@example.com"
                      className={cn(errors.email && 'border-danger-500 focus:ring-danger-500')}
                      aria-invalid={!!errors.email}
                      aria-describedby={errors.email ? 'email-error' : undefined}
                    />
                    {errors.email && (
                      <p id="email-error" className="mt-1 text-sm text-danger-500 flex items-center gap-1">
                        <AlertCircle className="w-3 h-3" />
                        {errors.email}
                      </p>
                    )}
                  </div>
                </div>

                {/* Subject */}
                <div>
                  <label
                    htmlFor="subject"
                    className="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2"
                  >
                    Subject <span className="text-danger-500">*</span>
                  </label>
                  <Input
                    id="subject"
                    name="subject"
                    type="text"
                    value={formData.subject}
                    onChange={handleChange}
                    placeholder="How can we help?"
                    className={cn(errors.subject && 'border-danger-500 focus:ring-danger-500')}
                    aria-invalid={!!errors.subject}
                    aria-describedby={errors.subject ? 'subject-error' : undefined}
                  />
                  {errors.subject && (
                    <p id="subject-error" className="mt-1 text-sm text-danger-500 flex items-center gap-1">
                      <AlertCircle className="w-3 h-3" />
                      {errors.subject}
                    </p>
                  )}
                </div>

                {/* Message */}
                <div>
                  <label
                    htmlFor="message"
                    className="block text-sm font-medium text-secondary-700 dark:text-secondary-300 mb-2"
                  >
                    Message <span className="text-danger-500">*</span>
                  </label>
                  <Textarea
                    id="message"
                    name="message"
                    value={formData.message}
                    onChange={handleChange}
                    placeholder="Tell us more about your inquiry..."
                    rows={6}
                    className={cn(errors.message && 'border-danger-500 focus:ring-danger-500')}
                    aria-invalid={!!errors.message}
                    aria-describedby={errors.message ? 'message-error' : undefined}
                  />
                  {errors.message && (
                    <p id="message-error" className="mt-1 text-sm text-danger-500 flex items-center gap-1">
                      <AlertCircle className="w-3 h-3" />
                      {errors.message}
                    </p>
                  )}
                </div>

                {/* Submit Button */}
                <Button
                  type="submit"
                  size="lg"
                  disabled={status === 'submitting' || status === 'success'}
                  className="w-full sm:w-auto"
                >
                  {status === 'submitting' ? (
                    <>
                      <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
                      Sending...
                    </>
                  ) : status === 'success' ? (
                    <>
                      <CheckCircle className="w-4 h-4 mr-2" />
                      Sent!
                    </>
                  ) : (
                    <>
                      <Send className="w-4 h-4 mr-2" />
                      Send Message
                    </>
                  )}
                </Button>
              </form>
            </div>
          </div>
        </div>

        {/* FAQ Section */}
        <div className="mt-16 pt-12 border-t border-secondary-200 dark:border-secondary-700">
          <H2 className="text-center mb-8">Frequently Asked Questions</H2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            <div className="p-6 rounded-xl bg-secondary-50 dark:bg-secondary-800/50">
              <h3 className="font-semibold mb-2">How quickly will I receive a response?</h3>
              <Text color="muted" className="text-sm">
                We typically respond to all inquiries within 24-48 hours during business days.
              </Text>
            </div>
            <div className="p-6 rounded-xl bg-secondary-50 dark:bg-secondary-800/50">
              <h3 className="font-semibold mb-2">Can I advertise on your platform?</h3>
              <Text color="muted" className="text-sm">
                Yes! We offer various advertising opportunities. Contact us for more details.
              </Text>
            </div>
            <div className="p-6 rounded-xl bg-secondary-50 dark:bg-secondary-800/50">
              <h3 className="font-semibold mb-2">How do I become a contributor?</h3>
              <Text color="muted" className="text-sm">
                Create an account and start writing! Our team reviews all new contributors.
              </Text>
            </div>
            <div className="p-6 rounded-xl bg-secondary-50 dark:bg-secondary-800/50">
              <h3 className="font-semibold mb-2">Do you offer technical support?</h3>
              <Text color="muted" className="text-sm">
                Yes, our support team is available to help with any technical issues you encounter.
              </Text>
            </div>
          </div>
        </div>
      </Container>
    </Section>
  );
}

export default ContactPage;
